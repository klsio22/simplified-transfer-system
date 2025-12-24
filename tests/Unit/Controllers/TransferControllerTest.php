<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\TransferController;
use App\Core\BusinessRuleException;
use App\Core\InvalidTransferException;
use App\Core\UserNotFoundException;
use App\Services\TransferService;
use PHPUnit\Framework\TestCase;
use Slim\Flash\Messages;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class TransferControllerTest extends TestCase
{
    public function testTransferProcessesValidTransfer(): void
    {
        $requestData = [
            'payer' => 1,
            'payee' => 2,
            'value' => 50.00,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->with($requestData)
            ->willReturn(['success' => true, 'notification_sent' => true]);

        $flash = $this->createMock(Messages::class);
        $flash->expects($this->once())
            ->method('addMessage')
            ->with('success', 'Transfer completed successfully');

        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['success']);
    }

    public function testTransferReturnsMissingFieldsError(): void
    {
        $requestData = [
            'payer' => 1,
            'payee' => 2,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new InvalidTransferException('Missing required fields'));

        $flash = $this->createMock(Messages::class);
        $flash->expects($this->once())
            ->method('addMessage')
            ->with('error', 'Missing required fields');

        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testTransferReturnsInvalidValueError(): void
    {
        $requestData = [
            'payer' => 1,
            'payee' => 2,
            'value' => -50.00,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new InvalidTransferException('Value must be greater than zero'));

        $flash = $this->createMock(Messages::class);
        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testTransferReturnsSamePayerPayeeError(): void
    {
        $requestData = [
            'payer' => 1,
            'payee' => 1,
            'value' => 50.00,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new BusinessRuleException('Cannot transfer to yourself'));

        $flash = $this->createMock(Messages::class);
        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testTransferReturnsUserNotFoundError(): void
    {
        $requestData = [
            'payer' => 999,
            'payee' => 2,
            'value' => 50.00,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new UserNotFoundException('User not found'));

        $flash = $this->createMock(Messages::class);
        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertStringContainsString('User not found', $body['error']);
    }

    public function testTransferReturnsInternalServerError(): void
    {
        $requestData = [
            'payer' => 1,
            'payee' => 2,
            'value' => 50.00,
        ];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new \Exception('Database error'));

        $flash = $this->createMock(Messages::class);
        $flash->expects($this->once())
            ->method('addMessage')
            ->with('error', 'Internal server error');

        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertStringContainsString('Internal server error', $body['error']);
    }

    public function testTransferHandlesEmptyPayload(): void
    {
        $requestData = [];

        $transferService = $this->createMock(TransferService::class);
        $transferService->expects($this->once())
            ->method('processPayload')
            ->willThrowException(new InvalidTransferException('Missing required fields'));

        $flash = $this->createMock(Messages::class);
        $controller = new TransferController($transferService, $flash);

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/transfer')
            ->withParsedBody($requestData);

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->transfer($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }
}
