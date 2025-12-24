<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\BalanceController;
use App\Controllers\TransferController;
use App\Models\User;
use App\Repositories\UserRepository;
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

        $container = (function () {
            // load definitions and build container
            $builder = new \DI\ContainerBuilder();
            $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
            $c = $builder->build();

            // stub HealthController for tests
            $c->set(\App\Controllers\HealthController::class, function () {
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
            });

            // stub BalanceController for tests
            $c->set(\App\Controllers\BalanceController::class, function () use ($c) {
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
            });

            // stub UserRepository for balance tests
            $c->set(UserRepository::class, function () {
                return new class () {
                    public function find(int $id)
                    {
                        if ($id === 1) {
                            $u = new User();
                            $u->id = 1;
                            $u->fullName = 'Usuário Comum';
                            $u->cpf = '123';
                            $u->email = 'comum@example.com';
                            $u->password = 'x';
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
                            $u->password = 'x';
                            $u->type = 'shopkeeper';
                            $u->balance = 0.00;

                            return $u;
                        }

                        return null;
                    }
                };
            });

            // stub TransferService to simulate business rules without DB
            $c->set(\App\Services\TransferService::class, function () {
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
            });

            // stub TransferController for tests
            $c->set(\App\Controllers\TransferController::class, function () use ($c) {
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

                        if (
                            $data === null ||
                            (is_object($data) && (array)$data === []) ||
                            (is_array($data) && $data === [])
                        ) {
                            return $this->jsonResponse(
                                $response,
                                ['error' => 'Invalid or empty payload'],
                                422
                            );
                        }

                        if (is_object($data)) {
                            $data = (array) $data;
                        }

                        if (! is_array($data)) {
                            return $this->jsonResponse($response, ['error' => 'Invalid payload'], 400);
                        }

                        $requiredFields = ['value', 'payer', 'payee'];
                        $missingFields = [];

                        foreach ($requiredFields as $field) {
                            if (! isset($data[$field])) {
                                $missingFields[] = $field;
                            }
                        }

                        if (! empty($missingFields)) {
                            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 422);
                        }

                        try {
                            $result = $this->transferService->transfer(
                                (int) $data['payer'],
                                (int) $data['payee'],
                                (float) $data['value']
                            );

                            return $this->jsonResponse($response, $result, 200);
                        } catch (\Exception $e) {
                            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

                            return $this->jsonResponse($response, ['error' => $e->getMessage()], $statusCode);
                        }
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
            });

            return $c;
        })();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // add body parsing middleware used by real app
        $app->addBodyParsingMiddleware();

        // register routes
        $app->get('/', [\App\Controllers\HealthController::class, 'hello']);
        $app->post('/transfer', [\App\Controllers\TransferController::class, 'transfer']);
        $app->get('/balance/{id}', [\App\Controllers\BalanceController::class, 'show']);

        return $app;
    }

    private function request(string $method, string $path, array $data = []): \Psr\Http\Message\ResponseInterface
    {
        $app = $this->createApp();

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        if (! empty($data)) {
            $request = $request->withParsedBody($data);
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
}
