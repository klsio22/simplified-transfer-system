<?php

declare(strict_types=1);

use App\Controllers\TransferController;
use App\Controllers\HealthController;
use App\Controllers\BalanceController;

/** @var \Slim\App $app */
$app->get('/', [HealthController::class, 'hello']);
$app->post('/transfer', [TransferController::class, 'transfer']);

// Balance endpoint
$app->get('/balance/{id}', [BalanceController::class, 'show']);
