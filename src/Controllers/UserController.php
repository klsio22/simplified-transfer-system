<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Flash\Messages as FlashMessages;

class UserController
{
    public function __construct(private UserService $userService, private ?FlashMessages $flash = null)
    {
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (! is_array($data)) {
            if ($this->flash !== null) {
                $this->flash->addMessage('error', 'Invalid data format');
            }

            return $this->jsonResponse($response, ['error' => true, 'message' => 'Dados inválidos'], 400);
        }

        try {
            $result = $this->userService->createUser($data);

            if ($this->flash !== null) {
                $this->flash->addMessage('success', 'User created successfully');
            }

            return $this->jsonResponse($response, $result, 201);
        } catch (AppException $e) {
            if ($this->flash !== null) {
                $this->flash->addMessage('error', $e->getMessage());
            }

            return $this->jsonResponse($response, ['error' => true, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            error_log('Unexpected error in user controller: ' . $e->getMessage());
            if ($this->flash !== null) {
                $this->flash->addMessage('error', 'Internal server error');
            }

            return $this->jsonResponse($response, ['error' => true, 'message' => 'Internal server error'], 500);
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

