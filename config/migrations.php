<?php

declare(strict_types=1);

return [
    'directory' => __DIR__ . '/../migrations',  // pasta onde ficam as migrations
    'table' => 'migrations',                // tabela que guarda o histórico
    'safe' => true,                        // modo seguro (produção)
];
