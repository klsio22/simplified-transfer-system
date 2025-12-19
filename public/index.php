<?php

declare(strict_types=1);

use App\Controllers\TransferController;
use App\Middleware\JsonMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Carrega variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Cria container DI
$container = require __DIR__ . '/../config/container.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

// Middleware de erro
$app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Middleware JSON
$app->add(JsonMiddleware::class);

// Rotas
$app->post('/transfer', [TransferController::class, 'transfer']);

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Transfer System API',
        'version' => '1.0.0',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
