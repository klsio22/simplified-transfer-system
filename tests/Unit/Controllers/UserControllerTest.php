<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\UserController;
use App\Core\InvalidTransferException;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Slim\Flash\Messages;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class UserControllerTest extends TestCase
{
    public function testStoreCreatesUserSuccessfully(): void
    {
        $requestData = [
            'full_name' => 'New User',
            'cpf' => '12345678900',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'type' => 'common',
        ];

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())
            ->method('createUser')
            ->with($requestData)
            ->willReturn(['success' => true, 'id' => 3]);

        $flash = $this->createMock(Messages::class);

        $controller = new UserController($userService, $flash);

        $factory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $request = $factory->createServerRequest('POST', '/users')
            ->withBody($streamFactory->createStream(json_encode($requestData)));

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->store($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(3, $body['id']);
    }

    public function testStoreReturnsValidationError(): void
    {
        $requestData = [
            'full_name' => 'Invalid User',
            'email' => 'invalid@example.com',
        ];

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())
            ->method('createUser')
            ->with($requestData)
            ->willThrowException(new InvalidTransferException('Missing required fields'));

        $flash = $this->createMock(Messages::class);

        $controller = new UserController($userService, $flash);

        $factory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $request = $factory->createServerRequest('POST', '/users')
            ->withBody($streamFactory->createStream(json_encode($requestData)));

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->store($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertArrayHasKey('message', $body);
    }

    public function testStoreReturnsInvalidJsonError(): void
    {
        $userService = $this->createMock(UserService::class);
        $flash = $this->createMock(Messages::class);

        $controller = new UserController($userService, $flash);

        $factory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $request = $factory->createServerRequest('POST', '/users')
            ->withBody($streamFactory->createStream('invalid json'));

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->store($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['error']);
    }

    public function testStoreReturnsInternalServerError(): void
    {
        $requestData = [
            'full_name' => 'Test User',
            'cpf' => '11111111111',
            'email' => 'test@example.com',
            'password' => 'password123',
            'type' => 'common',
        ];

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())
            ->method('createUser')
            ->willThrowException(new \Exception('Database error'));

        $flash = $this->createMock(Messages::class);

        $controller = new UserController($userService, $flash);

        $factory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $request = $factory->createServerRequest('POST', '/users')
            ->withBody($streamFactory->createStream(json_encode($requestData)));

        $response = (new ResponseFactory())->createResponse(200);
        $result = $controller->store($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Internal server error', $body['message']);
    }
}
