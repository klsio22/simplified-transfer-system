<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🚀 Iniciando worker de notificações...\n";

$container = require __DIR__ . '/../config/container.php';
$notifyService = $container->get(App\Services\NotifyService::class);

echo "✅ Worker iniciado. Aguardando notificações...\n";

// Loop infinito processando fila
$notifyService->processQueue();
