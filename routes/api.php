<?php

declare(strict_types=1);

use App\Controllers\TransferController;
use App\Controllers\HealthController;

/** @var \Slim\App $app */
$app->get('/', [HealthController::class, 'hello']);
$app->post('/transfer', [TransferController::class, 'transfer']);
