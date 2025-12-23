<?php

declare(strict_types=1);

use Cycle\Database\DatabaseManager;
use Cycle\Database\Config;

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

return new DatabaseManager(
    new Config\DatabaseConfig([
        'default'     => 'default',
        'databases'   => [
            'default' => ['connection' => 'mysql'],
        ],
        'connections' => [
            'mysql' => new Config\MySQLDriverConfig(
                connection: new Config\MySQL\DsnConnectionConfig(
                    dsn: 'mysql:host=' . ($_ENV['DB_HOST'] ?? 'mysql') .
                         ';dbname=' . ($_ENV['DB_NAME'] ?? 'simplified_transfer') .
                         ';port=' . ($_ENV['DB_PORT'] ?? '3306') .
                         ';charset=utf8mb4',
                    user: $_ENV['DB_USER'] ?? 'transfer_user',
                    password: $_ENV['DB_PASS'] ?? 'transfer_pass',
                ),
                queryCache: true,
            ),
        ],
    ])
);
