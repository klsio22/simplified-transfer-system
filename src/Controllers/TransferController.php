<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Services\TransferService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Flash\Messages as FlashMessages;

class TransferController
{
    public function __construct(
        private TransferService $transferService,
        private ?FlashMessages $flash = null,
        private ?LoggerInterface $logger = null
    ) {
    }

    public function transfer(Request $request, Response $response): Response
    {
        $raw = $this->extractRequestData($request);

        try {
            $result = $this->transferService->processPayload($raw);

            if ($this->flash !== null) {
                $this->flash->addMessage('success', 'Transfer completed successfully');
            }

            return $this->jsonResponse($response, $result, 200);
        } catch (AppException $e) {
            if ($this->flash !== null) {
                $this->flash->addMessage('error', $e->getMessage());
            }

            return $this->jsonResponse($response, ['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            // unexpected
            $this->logger?->warning('Unexpected error in transfer controller: ' . $e->getMessage());
            if ($this->flash !== null) {
                $this->flash->addMessage('error', 'Internal server error');
            }

            return $this->jsonResponse($response, ['error' => 'Internal server error'], 500);
        }
    }

    /**
     * @return array<string,mixed>|object|null
     */
    private function extractRequestData(Request $request): array|object|null
    {
        return $request->getParsedBody();
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
