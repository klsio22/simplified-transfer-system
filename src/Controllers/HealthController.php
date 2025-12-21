<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController
{
    /**
     * Endpoint GET /hello
     * Health check simples
     */
    public function hello(Request $request, Response $response): Response
    {
        $data = [
            'message' => 'Hello, World!',
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
