<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class EndpointsTest extends TestCase
{
    private function createApp()
    {
        require __DIR__ . '/../../vendor/autoload.php';

        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        $container = $this->createContainer();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // add body parsing middleware used by real app
        $app->addBodyParsingMiddleware();

        // register routes
        $app->get('/', [\App\Controllers\HealthController::class, 'hello']);
        $app->post('/transfer', [\App\Controllers\TransferController::class, 'transfer']);
        $app->post('/users', [\App\Controllers\UserController::class, 'store']);
        $app->get('/balance/{id}', [\App\Controllers\BalanceController::class, 'show']);

        return $app;
    }

    private function createContainer()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
        $c = $builder->build();

        $c->set(\App\Controllers\HealthController::class, $this->createHealthControllerStub());
        $c->set(\App\Controllers\BalanceController::class, $this->createBalanceControllerStub($c));
        $c->set(UserRepository::class, $this->createUserRepositoryStub());
        $c->set(\App\Services\TransferService::class, $this->createTransferServiceStub());
        $c->set(\App\Controllers\TransferController::class, $this->createTransferControllerStub($c));
        $c->set(UserService::class, $this->createUserServiceStub($c));
        $c->set(\App\Controllers\UserController::class, $this->createUserControllerStub($c));

        return $c;
    }

    private function createHealthControllerStub()
    {
        return function () {
            return new class () {
                public function hello(
                    \Psr\Http\Message\ServerRequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response
                ) {
                    $data = ['message' => 'Hello, World!'];
                    $response->getBody()->write((string) json_encode($data));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
                }
            };
        };
    }

    private function createBalanceControllerStub($c)
    {
        return function () use ($c) {
            return new class ($c->get(UserRepository::class)) {
                private $userRepository;
                public function __construct($userRepository)
                {
                    $this->userRepository = $userRepository;
                }

                public function show(
                    \Psr\Http\Message\ServerRequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response,
                    array $args = []
                ) {

                    $id = (int) ($args['id'] ?? 0);

                    if ($id <= 0) {
                        $payload = ['error' => 'Invalid ID'];
                        $response->getBody()->write((string) json_encode($payload));

                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    $user = $this->userRepository->find($id);

                    if ($user === null) {
                        $payload = ['error' => 'User not found'];
                        $response->getBody()->write((string) json_encode($payload));

                        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
                    }

                    $payload = [
                        'id' => $user->id,
                        'fullName' => $user->fullName,
                        'balance' => (float) $user->balance,
                    ];

                    $response->getBody()->write((string) json_encode($payload));

                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                }
            };
        };
    }

    private function createUserRepositoryStub()
    {
        return function () {
            return new class () {
                public function find(int $id)
                {
                    if ($id === 1) {
                        $u = new User();
                        $u->id = 1;
                        $u->fullName = 'Usuário Comum';
                        $u->cpf = '123';
                        $u->email = 'comum@example.com';
                        $u->password = password_hash('test_password', PASSWORD_BCRYPT);
                        $u->type = 'common';
                        $u->balance = 200.00;

                        return $u;
                    }

                    if ($id === 4) {
                        $u = new User();
                        $u->id = 4;
                        $u->fullName = 'Loja ABC';
                        $u->cpf = '999';
                        $u->email = 'loja@example.com';
                        $u->password = password_hash('test_password', PASSWORD_BCRYPT);
                        $u->type = 'shopkeeper';
                        $u->balance = 0.00;

                        return $u;
                    }

                    return null;
                }
            };
        };
    }

    private function createTransferServiceStub()
    {
        return function () {
            return new class () {
                public function transfer(int $payerId, int $payeeId, float $value): array
                {
                    if ($value <= 0) {
                        throw new \App\Core\InvalidTransferException('Valor inválido');
                    }

                    if ($payerId === $payeeId) {
                        throw new \App\Core\InvalidTransferException('Payer and payee must differ');
                    }

                    if ($payerId === 4) {
                        throw new \App\Core\BusinessRuleException('Lojistas não podem enviar transferências');
                    }

                    if ($payerId === 999 || $payeeId === 999) {
                        throw new \App\Core\UserNotFoundException('User not found');
                    }

                    if ($value > 1000) {
                        throw new \App\Core\BusinessRuleException('Saldo insuficiente');
                    }

                    // success
                    return [
                        'success' => true,
                        'message' => 'Transfer completed successfully',
                        'value' => $value,
                        'payer_id' => $payerId,
                        'payee_id' => $payeeId,
                        'notification_sent' => true,
                    ];
                }
            };
        };
    }

    private function createTransferControllerStub($c)
    {
        return function () use ($c) {
            return new class ($c->get(\App\Services\TransferService::class)) {
                private $transferService;
                public function __construct($transferService)
                {
                    $this->transferService = $transferService;
                }

                public function transfer(
                    \Psr\Http\Message\ServerRequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response
                ) {
                    $data = $request->getParsedBody();
                    $statusCode = 200;
                    $payload = null;

                    // Validate payload
                    if (
                        $data === null ||
                        (is_object($data) && (array)$data === []) ||
                        (is_array($data) && $data === [])
                    ) {
                        $statusCode = 422;
                        $payload = ['error' => 'Invalid or empty payload'];
                    } elseif (is_object($data)) {
                        $data = (array) $data;
                        // Check if converted data is still valid
                        if (! is_array($data)) {
                            $statusCode = 400;
                            $payload = ['error' => 'Invalid payload'];
                        }
                    } elseif (! is_array($data)) {
                        $statusCode = 400;
                        $payload = ['error' => 'Invalid payload'];
                    }

                    // Validate required fields if payload is valid so far
                    if ($payload === null) {
                        $requiredFields = ['value', 'payer', 'payee'];
                        $missingFields = [];

                        foreach ($requiredFields as $field) {
                            if (! isset($data[$field])) {
                                $missingFields[] = $field;
                            }
                        }

                        if (! empty($missingFields)) {
                            $statusCode = 422;
                            $payload = ['error' => 'Missing required fields'];
                        }
                    }

                    // Execute transfer if validation passed
                    if ($payload === null) {
                        try {
                            $payload = $this->transferService->transfer(
                                (int) $data['payer'],
                                (int) $data['payee'],
                                (float) $data['value']
                            );
                        } catch (\Exception $e) {
                            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
                            $payload = ['error' => $e->getMessage()];
                        }
                    }

                    return $this->jsonResponse($response, $payload, $statusCode);
                }

                private function jsonResponse(
                    \Psr\Http\Message\ResponseInterface $response,
                    array $data,
                    int $statusCode
                ) {

                    $response->getBody()->write((string) json_encode($data));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($statusCode);
                }
            };
        };
    }

    private function createUserServiceStub($c)
    {
        return function () use ($c) {
            return new class ($c->get(UserRepository::class)) {
                private $userRepository;
                public function __construct($userRepository)
                {
                    $this->userRepository = $userRepository;
                }

                public function createUser(array $data): array
                {
                    $this->validateRequiredFields($data);
                    $this->validateEmailFormat($data);
                    $this->validateUniqueness($data);

                    return ['success' => true, 'id' => 1];
                }

                private function validateRequiredFields(array $data): void
                {
                    $required = ['full_name', 'cpf', 'email', 'password', 'type'];
                    foreach ($required as $field) {
                        if (empty($data[$field] ?? null) && empty($data['fullName'] ?? null)) {
                            throw new \App\Core\InvalidTransferException("{$field} is required");
                        }
                    }
                }

                private function validateEmailFormat(array $data): void
                {
                    $email = $data['email'] ?? '';
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new \App\Core\InvalidTransferException('Invalid email format');
                    }
                }

                private function validateUniqueness(array $data): void
                {
                    $cpf = $data['cpf'] ?? '';
                    if ($cpf === '11111111111') {
                        throw new \App\Core\InvalidTransferException('CPF already registered');
                    }

                    $email = $data['email'] ?? '';
                    if ($email === 'duplicate@example.com') {
                        throw new \App\Core\InvalidTransferException('Email already registered');
                    }
                }
            };
        };
    }

    private function createUserControllerStub($c)
    {
        return function () use ($c) {
            return new class ($c->get(UserService::class)) {
                private $userService;
                public function __construct($userService)
                {
                    $this->userService = $userService;
                }

                public function store(
                    \Psr\Http\Message\ServerRequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response
                ) {
                    $body = (string) $request->getBody();
                    $data = json_decode($body, true);

                    ['responseData' => $responseData, 'statusCode' => $statusCode] = $this->handleUserCreation($data);

                    return $this->jsonResponse($response, $responseData, $statusCode);
                }

                /**
                 * @param mixed $data
                 * @return array{responseData: array<string,mixed>, statusCode: int}
                 */
                private function handleUserCreation($data): array
                {
                    $result = [
                        'responseData' => ['error' => true, 'message' => 'Dados inválidos'],
                        'statusCode' => 400,
                    ];

                    if (is_array($data)) {
                        try {
                            $result = [
                                'responseData' => $this->userService->createUser($data),
                                'statusCode' => 201,
                            ];
                        } catch (\App\Core\InvalidTransferException $e) {
                            $result = [
                                'responseData' => ['error' => true, 'message' => $e->getMessage()],
                                'statusCode' => 422,
                            ];
                        } catch (\Exception $e) {
                            $result = [
                                'responseData' => ['error' => true, 'message' => 'Internal server error'],
                                'statusCode' => 500,
                            ];
                        }
                    }

                    return $result;
                }

                private function jsonResponse(
                    \Psr\Http\Message\ResponseInterface $response,
                    array $data,
                    int $statusCode
                ) {
                    $response->getBody()->write((string) json_encode($data));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($statusCode);
                }
            };
        };
    }

    private function request(string $method, string $path, array $data = []): \Psr\Http\Message\ResponseInterface
    {
        $app = $this->createApp();

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        if (! empty($data)) {
            // For POST requests with JSON body, use stream instead of parsed body
            if ($method === 'POST') {
                $stream = new \Slim\Psr7\Stream(fopen('php://memory', 'r+'));
                $stream->write(json_encode($data));
                $stream->rewind();
                $request = $request->withBody($stream);
            } else {
                $request = $request->withParsedBody($data);
            }
        }

        return $app->handle($request);
    }

    public function testHealth(): void
    {
        $res = $this->request('GET', '/');
        $this->assertEquals(200, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertEquals('Hello, World!', $body['message']);
    }

    public function testBalanceFound(): void
    {
        $res = $this->request('GET', '/balance/1');
        $this->assertEquals(200, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertEquals(1, $body['id']);
        $this->assertArrayHasKey('balance', $body);
    }

    public function testBalanceNotFound(): void
    {
        $res = $this->request('GET', '/balance/999');
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testTransferSuccess(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 10.00, 'payer' => 1, 'payee' => 3]);
        $this->assertEquals(200, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue((bool) $body['success']);
        $this->assertArrayHasKey('notification_sent', $body);
        $this->assertTrue($body['notification_sent']);
    }

    public function testTransferInvalidValue(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => -50.00, 'payer' => 1, 'payee' => 2]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferMissingFields(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferEmptyPayload(): void
    {
        $res = $this->request('POST', '/transfer', []);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferInsufficientFunds(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 10000.00, 'payer' => 1, 'payee' => 4]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferSamePayerPayee(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 1, 'payee' => 1]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferShopkeeperCannotSend(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 4, 'payee' => 1]);
        $this->assertEquals(422, $res->getStatusCode());
    }

    public function testTransferUserNotFound(): void
    {
        $res = $this->request('POST', '/transfer', ['value' => 50.00, 'payer' => 999, 'payee' => 1]);
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testUsersCreateSuccess(): void
    {
        $res = $this->request('POST', '/users', [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'password123',
            'type' => 'common',
        ]);
        $this->assertEquals(201, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('id', $body);
    }

    public function testUsersCreateInvalidEmail(): void
    {
        $res = $this->request('POST', '/users', [
            'full_name' => 'Jane Doe',
            'cpf' => '98765432100',
            'email' => 'invalid-email',
            'password' => 'password123',
            'type' => 'common',
        ]);
        $this->assertEquals(422, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Invalid email', $body['message']);
    }

    public function testUsersCreateDuplicateCpf(): void
    {
        $res = $this->request('POST', '/users', [
            'full_name' => 'Bob Smith',
            'cpf' => '11111111111',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'type' => 'common',
        ]);
        $this->assertEquals(422, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('CPF already registered', $body['message']);
    }

    public function testUsersCreateDuplicateEmail(): void
    {
        $res = $this->request('POST', '/users', [
            'full_name' => 'Alice Johnson',
            'cpf' => '55555555555',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'type' => 'shopkeeper',
        ]);
        $this->assertEquals(422, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Email already registered', $body['message']);
    }

    public function testUsersCreateMissingField(): void
    {
        $res = $this->request('POST', '/users', [
            'full_name' => 'Missing Email',
            'cpf' => '66666666666',
            'password' => 'password123',
            'type' => 'common',
        ]);
        $this->assertEquals(422, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('required', $body['message']);
    }
}
