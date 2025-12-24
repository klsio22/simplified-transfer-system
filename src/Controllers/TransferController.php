<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Services\TransferService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Flash\Messages as FlashMessages;

class TransferController
{
    public function __construct(
        private TransferService $transferService,
        private ?FlashMessages $flash = null
    ) {
    }

    /**
     * Endpoint POST /transfer
     *
     * Payload esperado:
     * {
     *   "value": 100.00,
     *   "payer": 1,
     *   "payee": 2
     * }
     */
    public function transfer(Request $request, Response $response): Response
    {
        $raw = $this->extractRequestData($request);

        $payload = [];
        $status = 200;

        // Validação de campos obrigatórios
        $validation = $this->validatePayload($raw);
        if ($validation !== null) {
            $payload = $validation;
            $status = 400;
        } else {
            $data = $this->normalizeRequestData($raw);
            if ($data === null) {
                $payload = ['error' => 'Invalid payload'];
                $status = 400;
            } else {
                try {
                    $result = $this->executeTransferFromData($data);
                    $payload = $result;
                    $status = 200;
                } catch (Exception $e) {
                    $status = $this->getStatusCodeFromException($e);
                    if ($this->flash !== null) {
                        $this->flash->addMessage('error', $e->getMessage());
                    }

                    $payload = ['error' => $e->getMessage()];
                }
            }
        }

        return $this->jsonResponse($response, $payload, $status);
    }

    /**
     * Extract raw parsed body from the request.
     *
     * @return array<string,mixed>|object|null
     */
    private function extractRequestData(Request $request): array|object|null
    {
        return $request->getParsedBody();
    }

    /**
     * Normalize parsed body into an array or null if invalid.
     *
     * @param array<string,mixed>|object|null $data
     * @return array<string,mixed>|null
     */
    private function normalizeRequestData(array|object|null $data): ?array
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Perform the transfer using validated array data.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function executeTransferFromData(array $data): array
    {
        $result = $this->transferService->transfer(
            (int) $data['payer'],
            (int) $data['payee'],
            (float) $data['value']
        );

        if ($this->flash !== null) {
            $this->flash->addMessage('success', 'Transfer completed successfully');
        }

        return $result;
    }

    /**
     * Valida o payload da requisição
     *
     * @param array<string,mixed>|object|null $data
     * @return array<string,mixed>|null
     */
    private function validatePayload(array|object|null $data): ?array
    {
        if ($data === null) {
            return ['error' => 'Invalid or empty payload'];
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        $requiredFields = ['value', 'payer', 'payee'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return [
                'error' => 'Missing required fields',
                'missing_fields' => $missingFields,
            ];
        }

        // Validações adicionais
        if (! is_numeric($data['value']) || $data['value'] <= 0) {
            return ['error' => 'The "value" field must be a number greater than zero'];
        }

        if (! is_numeric($data['payer']) || $data['payer'] <= 0) {
            return ['error' => 'The "payer" field must be a valid ID'];
        }

        if (! is_numeric($data['payee']) || $data['payee'] <= 0) {
            return ['error' => 'The "payee" field must be a valid ID'];
        }

        return null;
    }

    /**
     * Determina status code HTTP baseado na exceção
     */
    private function getStatusCodeFromException(Exception $e): int
    {
        // Se é uma AppException, usa o método customizado
        if ($e instanceof AppException) {
            return $e->getStatusCode();
        }

        // Fallback: tenta usar o código da exception
        $code = $e->getCode();

        // Se o código já é um status HTTP válido, usa ele
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        // Senão, retorna 500 (Internal Server Error)
        return 500;
    }

    /**
     * Helper para criar respostas JSON
     */
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
