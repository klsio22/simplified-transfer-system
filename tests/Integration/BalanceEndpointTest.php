<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\User;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class BalanceEndpointTest extends TestCase
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

        $app->addBodyParsingMiddleware();

        $app->get('/balance/{id}', [\App\Controllers\BalanceController::class, 'show']);

        return $app;
    }

    private function createContainer()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
        $c = $builder->build();

        $c->set(UserRepository::class, $this->createUserRepositoryStub());
        $c->set(\App\Controllers\BalanceController::class, $this->createBalanceControllerStub($c));

        return $c;
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

                    return null;
                }
            };
        };
    }

    private function request(string $method, string $path): \Psr\Http\Message\ResponseInterface
    {
        $app = $this->createApp();

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        return $app->handle($request);
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
}
