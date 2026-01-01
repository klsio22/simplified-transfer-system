<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

class HealthController
{
    public function hello(Response $response): Response
    {
        $data = [
            'message' => 'Hello, World!',
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $response->getBody()->write((string) json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
