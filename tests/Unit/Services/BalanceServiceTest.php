<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\InvalidTransferException;
use App\Core\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\BalanceService;
use PHPUnit\Framework\TestCase;

class BalanceServiceTest extends TestCase
{
    private BalanceService $balanceService;
    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = $this->createMock(UserRepository::class);
        $this->balanceService = new BalanceService($this->mockRepository);
    }

    public function testGetBalanceReturnsUserSuccessfully(): void
    {
        $user = new User();
        $user->id = 1;
        $user->fullName = 'Test User';
        $user->balance = 100.00;

        $this->mockRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $result = $this->balanceService->getBalance(1);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(100.00, $result->balance);
    }

    public function testGetBalanceThrowsExceptionForZeroId(): void
    {
        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        $this->balanceService->getBalance(0);
    }

    public function testGetBalanceThrowsExceptionForNegativeId(): void
    {
        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        $this->balanceService->getBalance(-5);
    }

    public function testGetBalanceThrowsExceptionWhenUserNotFound(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $this->balanceService->getBalance(999);
    }

    public function testGetBalanceConvertsStringIdToInteger(): void
    {
        $user = new User();
        $user->id = 42;
        $user->balance = 250.50;

        $this->mockRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($user);

        $result = $this->balanceService->getBalance('42');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id);
    }
}
