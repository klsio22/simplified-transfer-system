<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\BalanceController;
use App\Core\InvalidTransferException;
use App\Core\UserNotFoundException;
use App\Models\User;
use App\Services\BalanceService;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

class BalanceControllerTest extends TestCase
{
    public function testShowReturnsUserBalance(): void
    {
        $user = new User();
        $user->id = 1;
        $user->fullName = 'Test User';
        $user->balance = 250.50;

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('getBalance')
            ->with(1)
            ->willReturn($user);

        $controller = new BalanceController($balanceService);
        $response = (new ResponseFactory())->createResponse(200);
        $args = ['id' => 1];

        $result = $controller->show($response, $args);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(1, $body['id']);
        $this->assertEquals('Test User', $body['fullName']);
        $this->assertEquals(250.50, $body['balance']);
    }

    public function testShowReturns404WhenUserNotFound(): void
    {
        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('getBalance')
            ->with(999)
            ->willThrowException(new UserNotFoundException('User not found'));

        $controller = new BalanceController($balanceService);
        $response = (new ResponseFactory())->createResponse(200);
        $args = ['id' => 999];

        $result = $controller->show($response, $args);

        $this->assertEquals(404, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testShowReturns422ForInvalidId(): void
    {
        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('getBalance')
            ->with(-1)
            ->willThrowException(new InvalidTransferException('User ID must be a positive integer'));

        $controller = new BalanceController($balanceService);
        $response = (new ResponseFactory())->createResponse(200);
        $args = ['id' => -1];

        $result = $controller->show($response, $args);

        $this->assertEquals(422, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testShowReturns500OnUnexpectedError(): void
    {
        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('getBalance')
            ->willThrowException(new \Exception('Database error'));

        $controller = new BalanceController($balanceService);
        $response = (new ResponseFactory())->createResponse(200);
        $args = ['id' => 1];

        $result = $controller->show($response, $args);

        $this->assertEquals(500, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertStringContainsString('Internal server error', $body['error']);
    }
}
