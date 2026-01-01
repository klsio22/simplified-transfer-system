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
            $this->addFlashMessage('error', 'Invalid data format');

            return $this->jsonResponse($response, ['error' => true, 'message' => 'Dados inválidos'], 400);
        }

        ['responseData' => $responseData, 'statusCode' => $statusCode] = $this->handleUserCreation($data);

        return $this->jsonResponse($response, $responseData, $statusCode);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{responseData: array<string,mixed>, statusCode: int}
     */
    private function handleUserCreation(array $data): array
    {
        try {
            $result = $this->userService->createUser($data);
            $this->addFlashMessage('success', 'User created successfully');

            return [
                'responseData' => $result,
                'statusCode' => 201,
            ];
        } catch (AppException $e) {
            $this->addFlashMessage('error', $e->getMessage());

            return [
                'responseData' => ['error' => true, 'message' => $e->getMessage()],
                'statusCode' => $e->getStatusCode(),
            ];
        } catch (\Throwable $e) {
            error_log('Unexpected error in user controller: ' . $e->getMessage());
            $this->addFlashMessage('error', 'Internal server error');

            return [
                'responseData' => ['error' => true, 'message' => 'Internal server error'],
                'statusCode' => 500,
            ];
        }
    }

    private function addFlashMessage(string $key, string $message): void
    {
        if ($this->flash !== null) {
            $this->flash->addMessage($key, $message);
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
