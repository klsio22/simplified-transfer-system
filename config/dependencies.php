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
    DatabaseManager::class => function () {
        return require __DIR__ . '/database.php';
    },

    ORM::class => function () {
        return require __DIR__ . '/orm.php';
    },

    EntityManager::class => function (ContainerInterface $c) {
        return new EntityManager($c->get(ORM::class));
    },

    // Redis connection for distributed locking
    'Redis' => function () {
        $host = $_ENV['REDIS_HOST'] ?? 'redis';
        $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $db = (int) ($_ENV['REDIS_DB'] ?? 0);
        $timeout = 2.0;

        $redis = new \Redis();

        try {
            $redis->connect($host, $port, $timeout);

            if (array_key_exists('REDIS_PASSWORD', $_ENV)) {
                $redis->auth($_ENV['REDIS_PASSWORD']);
            }

            $redis->select($db);

            return $redis;
        } catch (\Throwable $e) {
            $env = $_ENV['APP_ENV'] ?? '';
            if ($env === 'test' || $env === 'local') {
                return new class () {
                    private array $data = [];

                    public function set($k, $v)
                    {
                        $this->data[$k] = $v;

                        return true;
                    }

                    public function get($k)
                    {
                        return $this->data[$k] ?? null;
                    }

                    public function del($k)
                    {
                        if (isset($this->data[$k])) {
                            unset($this->data[$k]);

                            return 1;
                        }

                        return 0;
                    }

                    public function exists($k)
                    {
                        return isset($this->data[$k]) ? 1 : 0;
                    }

                    public function eval($keys)
                    {
                        $key = $keys[0] ?? null;
                        $token = $keys[1] ?? null;
                        if ($key && ($this->data[$key] ?? null) === $token) {
                            unset($this->data[$key]);

                            return 1;
                        }

                        return 0;
                    }

                    public function select()
                    {
                        return true;
                    }

                    public function auth()
                    {
                        return true;
                    }
                };
            }

            throw new \RuntimeException(
                sprintf('Failed to connect to Redis at %s:%d (db %d): %s', $host, $port, $db, $e->getMessage()),
                0,
                $e
            );
        }
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
    RedisLockService::class => \DI\create()
        ->constructor(\DI\get('Redis')),
    TransferService::class => \DI\autowire(),
    UserService::class => \DI\autowire(),
    BalanceService::class => \DI\autowire(),
    UserRepository::class => \DI\autowire(),
    Messages::class => function () {
        return new Messages();
    },
];
