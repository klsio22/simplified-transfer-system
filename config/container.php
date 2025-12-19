<?php

declare(strict_types=1);

use App\Controllers\TransferController;
use App\Middleware\JsonMiddleware;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\AuthorizeService;
use App\Services\NotifyService;
use App\Services\TransferService;
use DI\Container;
use GuzzleHttp\Client;
use Predis\Client as Redis;
use Psr\Container\ContainerInterface;

$container = new Container();

// PDO
$container->set(PDO::class, function () {
    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $dbname = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
});

// Redis
$container->set(Redis::class, function () {
    try {
        return new Redis([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => (int) $_ENV['REDIS_PORT'],
        ]);
    } catch (\Exception $e) {
        error_log('Redis connection failed: ' . $e->getMessage());
        return null;
    }
});

// HTTP Client
$container->set(Client::class, function () {
    return new Client([
        'verify' => false,
        'http_errors' => false,
    ]);
});

// Repositories
$container->set(UserRepository::class, function (ContainerInterface $c) {
    return new UserRepository($c->get(PDO::class));
});

$container->set(WalletRepository::class, function (ContainerInterface $c) {
    return new WalletRepository($c->get(PDO::class));
});

$container->set(TransactionRepository::class, function (ContainerInterface $c) {
    return new TransactionRepository($c->get(PDO::class));
});

// Services
$container->set(AuthorizeService::class, function (ContainerInterface $c) {
    return new AuthorizeService($c->get(Client::class));
});

$container->set(NotifyService::class, function (ContainerInterface $c) {
    return new NotifyService($c->get(Client::class), $c->get(Redis::class));
});

$container->set(TransferService::class, function (ContainerInterface $c) {
    return new TransferService(
        $c->get(PDO::class),
        $c->get(UserRepository::class),
        $c->get(WalletRepository::class),
        $c->get(TransactionRepository::class),
        $c->get(AuthorizeService::class),
        $c->get(NotifyService::class)
    );
});

// Controllers
$container->set(TransferController::class, function (ContainerInterface $c) {
    return new TransferController($c->get(TransferService::class));
});

// Middleware
$container->set(JsonMiddleware::class, function () {
    return new JsonMiddleware();
});

return $container;
