<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use App\Controllers\TransferController;

class RoutesTest extends TestCase
{
    private function createApp()
    {
        // Carrega autoload e env (como em public/index.php)
        require __DIR__ . '/../../vendor/autoload.php';

        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        // Carrega container e sobrescreve o TransferController com um stub
        $container = require __DIR__ . '/../../config/container.php';

        // Stub simples para focar nos testes de rota (sem acessar DB)
        $container->set(TransferController::class, function () {
            return new class {
                public function transfer($request, $response)
                {
                    $response->getBody()->write(json_encode(['stub' => true]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                }
            };
        });

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Registrar as mesmas rotas do app
        $app->post('/transfer', [TransferController::class, 'transfer']);

        $app->get('/', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'message' => 'Transfer System API',
                'version' => '1.0.0',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        return $app;
    }

    public function testRootRouteReturnsInfo(): void
    {
        $app = $this->createApp();

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertEquals('Transfer System API', $data['message']);
        $this->assertArrayHasKey('version', $data);
    }

    public function testTransferRouteUsesControllerStub(): void
    {
        $app = $this->createApp();

        $requestFactory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();

        $request = $requestFactory->createServerRequest('POST', '/transfer')
            ->withBody($streamFactory->createStream(json_encode(['value' => 1, 'payer' => 1, 'payee' => 2])))
            ->withHeader('Content-Type', 'application/json');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertTrue((bool) $data['stub']);
    }
}
