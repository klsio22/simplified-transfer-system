<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class BalanceController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * GET /balance/{id}
     *
     * @param array<string,mixed> $args
     */
    public function show(Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            $payload = ['error' => 'Invalid ID'];
            $response->getBody()->write((string) json_encode($payload));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $this->userRepository->find($id);

        if ($user === null) {
            $payload = ['error' => 'User not found'];
            $response->getBody()->write((string) json_encode($payload));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $payload = [
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'balance' => (float) $user->getBalance(),
        ];

        $response->getBody()->write((string) json_encode($payload));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
