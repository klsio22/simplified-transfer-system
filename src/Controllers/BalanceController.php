<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\UserRepository;

class BalanceController
{
    public function __construct(private UserRepository $userRepository) {}

    /**
     * GET /balance/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            $payload = ['error' => 'Invalid ID'];
            $response->getBody()->write(json_encode($payload));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $this->userRepository->find($id);

        if ($user === null) {
            $payload = ['error' => 'User not found'];
            $response->getBody()->write(json_encode($payload));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $payload = [
            'id' => $user->getId(),
            'full_name' => $user->getFullName(),
            'balance' => (float) $user->getBalance(),
        ];

        $response->getBody()->write(json_encode($payload));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
