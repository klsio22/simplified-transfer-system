<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class HealthEndpointTest extends TestCase
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

        $app->get('/', [\App\Controllers\HealthController::class, 'hello']);

        return $app;
    }

    private function createContainer()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(require __DIR__ . '/../../config/dependencies.php');
        $c = $builder->build();

        $c->set(\App\Controllers\HealthController::class, $this->createHealthControllerStub());

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

    private function request(string $method, string $path): \Psr\Http\Message\ResponseInterface
    {
        $app = $this->createApp();

        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path)
            ->withHeader('Content-Type', 'application/json');

        return $app->handle($request);
    }

    public function testHealth(): void
    {
        $res = $this->request('GET', '/');
        $this->assertEquals(200, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertEquals('Hello, World!', $body['message']);
    }
}
