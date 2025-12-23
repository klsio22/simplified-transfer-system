<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\EntityManager;
use Cycle\Database\DatabaseManager;
use App\Services\AuthorizeService;
use App\Services\NotifyService;
use App\Services\TransferService;
use App\Repositories\UserRepository;
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

    AuthorizeService::class => DI\create(),
    NotifyService::class => DI\create(),
    TransferService::class => DI\autowire(),
    UserRepository::class => DI\autowire(),
    Messages::class => function () {
        return new Messages();
    },
];
