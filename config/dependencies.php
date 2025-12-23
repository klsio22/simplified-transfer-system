<?php

declare(strict_types=1);

use PDO;
use Psr\Container\ContainerInterface;
use App\Services\AuthorizeService;
use App\Services\NotifyService;
use App\Services\TransferService;
use App\Repositories\UserRepository;
use Slim\Flash\Messages;

return [
    PDO::class => function (ContainerInterface $c) {
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
