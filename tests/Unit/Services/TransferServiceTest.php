<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthorizeService;
use App\Services\NotifyService;
use App\Services\RedisLockService;
use App\Services\TransferService;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;

class TransferServiceTest extends TestCase
{
    private TransferService $transferService;
    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $userRepository;
    /** @var AuthorizeService&\PHPUnit\Framework\MockObject\MockObject */
    private AuthorizeService $authorizeService;
    /** @var NotifyService&\PHPUnit\Framework\MockObject\MockObject */
    private NotifyService $notifyService;
    /** @var RedisLockService&\PHPUnit\Framework\MockObject\MockObject */
    private RedisLockService $redisLockService;
    /** @var PDO&\PHPUnit\Framework\MockObject\MockObject */
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = $this->createMock(PDO::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->authorizeService = $this->createMock(AuthorizeService::class);
        $this->notifyService = $this->createMock(NotifyService::class);
        $this->redisLockService = $this->createMock(RedisLockService::class);

        $this->userRepository->method('getPdo')->willReturn($this->pdo);

        $this->redisLockService
            ->method('acquireLocks')
            ->willReturnCallback(function (int $userId1, int $userId2): array {
                $firstId = $userId1 < $userId2 ? $userId1 : $userId2;
                $secondId = $userId1 < $userId2 ? $userId2 : $userId1;

                return [
                    'lock1' => "token_{$firstId}",
                    'lock2' => "token_{$secondId}",
                    'id1' => $firstId,
                    'id2' => $secondId,
                ];
            });

        $this->transferService = new TransferService(
            $this->userRepository,
            $this->authorizeService,
            $this->notifyService,
            $this->redisLockService
        );
    }

    public function testTransferWithShopkeeperAsPayerShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Shopkeepers cannot perform transfers');
        $this->expectExceptionCode(422);

        $payer = $this->createShopkeeper(1, 100.0);
        $payee = $this->createCommonUser(2, 0.0);

        $this->userRepository
            ->method('find')
            ->willReturnMap([
                [1, $payer],
                [2, $payee],
            ]);

        $this->transferService->transfer(1, 2, 50.0);
    }

    public function testTransferSuccessfullyNotifiesAsynchronously(): void
    {
        $payer = $this->createCommonUser(1, 100.0);
        $payee = $this->createCommonUser(2, 0.0);

        $this->userRepository
            ->method('find')
            ->willReturnMap([
                [1, $payer],
                [2, $payee],
            ]);

        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('inTransaction')->willReturn(false);

        $this->authorizeService->method('isAuthorized')->willReturn(true);

        $this->notifyService
            ->expects($this->once())
            ->method('notify')
            ->with(2);

        $result = $this->transferService->transfer(1, 2, 50.0);

        $this->assertTrue($result['notification_sent']);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['payee_id']);
        $this->assertEquals(50.0, $result['value']);
    }

    public function testTransferFailsWhenAuthorizationServiceDenies(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction not authorized by authorization service');
        $this->expectExceptionCode(422);

        $payer = $this->createCommonUser(1, 100.0);
        $payee = $this->createCommonUser(2, 0.0);

        $this->userRepository
            ->method('find')
            ->willReturnMap([
                [1, $payer],
                [2, $payee],
            ]);

        // Authorization service denies (or external timed out)
        $this->authorizeService->method('isAuthorized')->willReturn(false);

        $this->transferService->transfer(1, 2, 50.0);
    }

    public function testTransferWithInsufficientBalanceShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        $this->expectExceptionCode(422);

        $payer = $this->createCommonUser(1, 30.0);
        $payee = $this->createCommonUser(2, 0.0);

        $this->userRepository
            ->method('find')
            ->willReturnMap([
                [1, $payer],
                [2, $payee],
            ]);

        $this->transferService->transfer(1, 2, 50.0);
    }

    public function testTransferWithZeroValueShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transfer value must be greater than zero');
        $this->expectExceptionCode(422);

        $this->transferService->transfer(1, 2, 0.0);
    }

    public function testTransferWithNegativeValueShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transfer value must be greater than zero');
        $this->expectExceptionCode(422);

        $this->transferService->transfer(1, 2, -10.0);
    }

    public function testTransferToSelfShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot transfer to yourself');
        $this->expectExceptionCode(422);

        $this->transferService->transfer(1, 1, 50.0);
    }

    public function testTransferWithNonExistentPayerShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Payer not found');
        $this->expectExceptionCode(404);

        $this->userRepository
            ->method('find')
            ->willReturn(null);

        $this->transferService->transfer(999, 2, 50.0);
    }

    public function testTransferWithNonExistentPayeeShouldFail(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Payee not found');
        $this->expectExceptionCode(404);

        $payer = $this->createCommonUser(1, 100.0);

        $this->userRepository
            ->method('find')
            ->willReturnMap([
                [1, $payer],
                [999, null],
            ]);

        $this->transferService->transfer(1, 999, 50.0);
    }

    private function createCommonUser(int $id, float $balance): User
    {
        $user = new User();
        $user->id = $id;
        $user->fullName = "Common User {$id}";
        $user->cpf = "12345678900";
        $user->email = "user{$id}@example.com";
        $user->type = 'common';
        $user->balance = $balance;

        return $user;
    }

    private function createShopkeeper(int $id, float $balance): User
    {
        $user = new User();
        $user->id = $id;
        $user->fullName = "Shopkeeper {$id}";
        $user->cpf = "98765432100";
        $user->email = "shop{$id}@example.com";
        $user->type = 'shopkeeper';
        $user->balance = $balance;

        return $user;
    }
}
