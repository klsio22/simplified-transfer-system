<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TransferService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransferController
{
    public function __construct(
        private TransferService $transferService
    ) {
    }

    public function transfer(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                $response->getBody()->write(json_encode([
                    'error' => true,
                    'message' => 'Dados inválidos',
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $result = $this->transferService->transfer($data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\App\Exceptions\AppException $e) {
            $response->getBody()->write(json_encode($e->toArray()));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getStatusCode());
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error' => true,
                'message' => 'Erro interno do servidor',
                'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null,
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
