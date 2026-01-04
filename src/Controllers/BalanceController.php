<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Services\BalanceService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class BalanceController
{
    public function __construct(private BalanceService $balanceService, private ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param array<string,mixed> $args
     */
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $_request
     */
    public function show(Request $_request, Response $response, array $args): Response
    {
        $userId = $args['id'] ?? null;

        try {
            $user = $this->balanceService->getBalance($userId);

            $payload = [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'balance' => (float) $user->getBalance(),
            ];

            return $this->jsonResponse($response, $payload, 200);
        } catch (AppException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            $this->logger?->warning('Unexpected error in balance controller: ' . $e->getMessage());

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
