<?php

declare(strict_types=1);

use App\Controllers\TransferController;

/** @var \Slim\App $app */
$app->post('/transfer', [TransferController::class, 'transfer']);
