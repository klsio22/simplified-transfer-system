<?php

declare(strict_types=1);

use App\Repositories\UserRepository;
use App\Services\AuthorizeService;
use App\Services\BalanceService;
use App\Services\NotifyService;
use App\Services\RedisLockService;
use App\Services\TransferService;
use App\Services\UserService;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;
use Psr\Container\ContainerInterface;
use Slim\Flash\Messages;

return [
    // DatabaseManager and ORM (Cycle)
    DatabaseManager::class => function (ContainerInterface $c) {
        return require __DIR__ . '/database.php';
    },

    ORM::class => function (ContainerInterface $c) {
        return require __DIR__ . '/orm.php';
    },

    EntityManager::class => function (ContainerInterface $c) {
        return new EntityManager($c->get(ORM::class));
    },

    // Redis connection for distributed locking
    'Redis' => function (ContainerInterface $c) {
        $redis = new \Redis();
        $redis->connect(
            $_ENV['REDIS_HOST'] ?? 'redis',
            (int) ($_ENV['REDIS_PORT'] ?? 6379),
            2.0 // timeout
        );

        if (! empty($_ENV['REDIS_PASSWORD'])) {
            $redis->auth($_ENV['REDIS_PASSWORD']);
        }

        $redis->select((int) ($_ENV['REDIS_DB'] ?? 0));

        return $redis;
    },

    // Legacy PDO (if still needed)
    \PDO::class => function (ContainerInterface $c) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        );

        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    },

    // Services
    AuthorizeService::class => \DI\create(),
    NotifyService::class => \DI\create(),
    RedisLockService::class => \DI\autowire(),
    TransferService::class => \DI\autowire(),
    UserService::class => \DI\autowire(),
    BalanceService::class => \DI\autowire(),
    UserRepository::class => \DI\autowire(),
    Messages::class => function () {
        return new Messages();
    },
];
