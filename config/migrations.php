<?php

declare(strict_types=1);

use Cycle\Migrations\Config\MigrationConfig;

return [
    'directory' => __DIR__ . '/../migrations',  // pasta onde ficam as migrations
    'table'     => 'migrations',                // tabela que guarda o histórico
    'safe'      => true,                        // modo seguro (produção)
];