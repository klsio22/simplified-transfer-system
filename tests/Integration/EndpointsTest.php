<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use App\Models\User;
use App\Controllers\TransferController;
use App\Controllers\BalanceController;
use App\Repositories\UserRepository;

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

            // stub UserRepository for balance tests
            $c->set(UserRepository::class, function () {
                return new class {
                    public function find(int $id)
                    {
                        if ($id === 1) {
                            $u = new User();
                            $u->id = 1;
                            $u->full_name = 'Usuário Comum';
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
                            $u->full_name = 'Loja ABC';
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
                return new class {
                    public function transfer($data)
                    {
                        if (!is_array($data)) {
                            throw new \Exception('Dados inválidos', 422);
                        }

                        $required = ['value', 'payer', 'payee'];
                        foreach ($required as $f) {
                            if (!array_key_exists($f, $data)) {
                                throw new \Exception('Campo obrigatório ausente', 422);
                            }
                        }

                        $value = (float) $data['value'];
                        $payer = (int) $data['payer'];
                        $payee = (int) $data['payee'];

                        if ($value <= 0) {
                            throw new \Exception('Valor inválido', 422);
                        }

                        if ($payer === $payee) {
                            throw new \Exception('Payer and payee must differ', 422);
                        }

                        if ($payer === 4) {
                            throw new \Exception('Lojistas não podem enviar transferências', 422);
                        }

                        if ($payer === 999 || $payee === 999) {
                            throw new \Exception('User not found', 404);
                        }

                        if ($value > 1000) {
                            throw new \Exception('Saldo insuficiente', 422);
                        }

                        // success
                        return ['success' => true, 'transaction' => ['id' => 123, 'value' => $value]];
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

        if (!empty($data)) {
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
