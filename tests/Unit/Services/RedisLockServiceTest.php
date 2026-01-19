<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\LockAcquisitionException;
use App\Services\RedisLockService;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\Mocks\MockRedis;

class RedisLockServiceTest extends TestCase
{
    private MockRedis $redis;
    private RedisLockService $lockService;

    protected function setUp(): void
    {
        $this->redis = new MockRedis();
        $this->lockService = new RedisLockService($this->redis);
    }

    public function testAcquireAndReleaseLocks(): void
    {
        $locks = $this->lockService->acquireLocks(userId1: 1, userId2: 2);

        $this->assertNotEmpty($locks['lock1']);
        $this->assertNotEmpty($locks['lock2']);
        $this->assertEquals(1, $locks['id1']);
        $this->assertEquals(2, $locks['id2']);

        $this->lockService->releaseLocks($locks);
    }

    public function testDeterministicLockOrdering(): void
    {
        $locks1 = $this->lockService->acquireLocks(userId1: 5, userId2: 3);
        $this->assertEquals(3, $locks1['id1']);
        $this->assertEquals(5, $locks1['id2']);

        $this->lockService->releaseLocks($locks1);

        $locks2 = $this->lockService->acquireLocks(userId1: 3, userId2: 5);
        $this->assertEquals(3, $locks2['id1']);
        $this->assertEquals(5, $locks2['id2']);

        $this->lockService->releaseLocks($locks2);
    }

    public function testLockTimeout(): void
    {
        $locks = $this->lockService->acquireLocks(userId1: 10, userId2: 20);
        $this->assertNotEmpty($locks['lock1']);

        $this->expectException(LockAcquisitionException::class);
        $this->lockService->acquireLocks(userId1: 10, userId2: 20);
    }

    public function testForceReleaseLock(): void
    {
        $locks = $this->lockService->acquireLocks(userId1: 7, userId2: 8);

        $this->lockService->forceReleaseLock(7);

        $this->assertFalse($this->redis->exists('transfer:lock:7') > 0);
    }

    public function testIsLocked(): void
    {
        $locks = $this->lockService->acquireLocks(userId1: 15, userId2: 25);

        $this->assertTrue($this->lockService->isLocked(15));
        $this->assertTrue($this->lockService->isLocked(25));

        $this->lockService->releaseLocks($locks);

        $this->assertFalse($this->lockService->isLocked(15));
        $this->assertFalse($this->lockService->isLocked(25));
    }
}
