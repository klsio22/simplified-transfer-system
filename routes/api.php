<?php

declare(strict_types=1);

use App\Controllers\TransferController;
use App\Controllers\HealthController;
use App\Controllers\BalanceController;
use App\Controllers\UserController;

/** @var \Slim\App $app */
$app->get('/', [HealthController::class, 'hello']);
$app->post('/transfer', [TransferController::class, 'transfer']);

// Users
$app->post('/users', [UserController::class, 'store']);

// Balance endpoint
$app->get('/balance/{id}', [BalanceController::class, 'show']);
