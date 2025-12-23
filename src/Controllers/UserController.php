<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            $payload = ['error' => true, 'message' => 'Dados inválidos'];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $required = ['fullName', 'cpf', 'email', 'password', 'type'];
        $errors = [];

        foreach ($required as $f) {
            if (empty($data[$f])) {
                $errors[$f] = 'Campo obrigatório';
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if (!empty($data['type']) && !in_array($data['type'], ['common', 'shopkeeper'], true)) {
            $errors['type'] = 'Tipo inválido (common|shopkeeper)';
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['error' => true, 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // Uniqueness checks
        if ($this->userRepository->findByCpf($data['cpf']) !== null) {
            $response->getBody()->write(json_encode(['error' => true, 'message' => 'CPF já cadastrado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        if ($this->userRepository->findByEmail($data['email']) !== null) {
            $response->getBody()->write(json_encode(['error' => true, 'message' => 'Email já cadastrado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // Create user
        $user = new User();
        $user->fullName = (string) ($data['fullName'] ?? $data['full_name']);
        $user->cpf = (string) $data['cpf'];
        $user->email = (string) $data['email'];
        $user->password = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $user->type = (string) $data['type'];
        $user->balance = isset($data['balance']) ? (float) $data['balance'] : 0.0;

        $id = $this->userRepository->create($user);

        $payload = ['success' => true, 'id' => $id];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
