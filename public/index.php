<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Carrega .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Container DI (PHP-DI)
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Middlewares globais
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(
    displayErrorDetails: $_ENV['APP_ENV'] === 'development',
    logErrors: true,
    logErrorDetails: true
);

// Rotas
require __DIR__ . '/../routes/api.php';

$app->run();
