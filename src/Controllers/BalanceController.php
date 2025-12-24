<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Services\BalanceService;
use Psr\Http\Message\ResponseInterface as Response;

class BalanceController
{
    public function __construct(private BalanceService $balanceService)
    {
    }

    /**
     * GET /balance/{id}
     *
     * @param array<string,mixed> $args
     */
    public function show(Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;

        try {
            $user = $this->balanceService->getBalance($id);

            $payload = [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'balance' => (float) $user->getBalance(),
            ];

            return $this->jsonResponse($response, $payload, 200);
        } catch (AppException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            error_log('Unexpected error in balance controller: ' . $e->getMessage());

            return $this->jsonResponse($response, ['error' => 'Internal server error'], 500);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function jsonResponse(Response $response, array $data, int $statusCode): Response
    {
        $response->getBody()->write((string) json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
