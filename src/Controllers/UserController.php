<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Flash\Messages as FlashMessages;

class UserController
{
    public function __construct(private UserRepository $userRepository, private ?FlashMessages $flash = null)
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

        // accept both `fullName` and `full_name` from clients
        $required = ['full_name', 'cpf', 'email', 'password', 'type'];
        $errors = [];

        foreach ($required as $f) {
            if (empty($data[$f]) && empty($data[camel_case($f)] ?? null)) {
                $errors[$f] = 'Required field';
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }

        if (!empty($data['type']) && !in_array($data['type'], ['common', 'shopkeeper'], true)) {
            $errors['type'] = 'Invalid type (common|shopkeeper)';
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['error' => true, 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // Uniqueness checks
        if ($this->userRepository->findByCpf($data['cpf']) !== null) {
            $message = ['error' => true, 'message' => 'CPF already registered'];
            if ($this->flash !== null) {
                $this->flash->addMessage('error', 'CPF already registered');
            }
            $response->getBody()->write(json_encode($message));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        if ($this->userRepository->findByEmail($data['email']) !== null) {
            $message = ['error' => true, 'message' => 'Email already registered'];
            if ($this->flash !== null) {
                $this->flash->addMessage('error', 'Email already registered');
            }
            $response->getBody()->write(json_encode($message));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // Create user
        $user = new User();
        $user->fullName = (string) ($data['fullName'] ?? $data['full_name'] ?? '');
        $user->cpf = (string) $data['cpf'];
        $user->email = (string) $data['email'];
        $user->password = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $user->type = (string) $data['type'];
        $user->balance = isset($data['balance']) ? (float) $data['balance'] : 0.0;

        $id = $this->userRepository->create($user);

        if ($this->flash !== null) {
            $this->flash->addMessage('success', 'User created successfully');
        }

        $payload = ['success' => true, 'id' => $id];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}

/**
 * Helper to convert snake_case field to camelCase
 */
function camel_case(string $s): string
{
    $parts = explode('_', $s);
    $camel = array_shift($parts);
    foreach ($parts as $p) {
        $camel .= ucfirst($p);
    }
    return $camel;
}
