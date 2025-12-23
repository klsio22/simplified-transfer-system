<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\TransferService;
use Slim\Flash\Messages as FlashMessages;
use Exception;

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
        $data = $request->getParsedBody();

        // Validação de campos obrigatórios
        $validation = $this->validatePayload($data);
        if ($validation !== null) {
            return $this->jsonResponse($response, $validation, 400);
        }

        // Ensure $data is an array for offset access
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return $this->jsonResponse($response, ['error' => 'Invalid payload'], 400);
        }

        try {
            // Executa transferência
            $result = $this->transferService->transfer(
                (int) $data['payer'],
                (int) $data['payee'],
                (float) $data['value']
            );

            // add flash message if available
            if ($this->flash !== null) {
                $this->flash->addMessage('success', 'Transfer completed successfully');
            }

            return $this->jsonResponse($response, $result, 200);
        } catch (Exception $e) {
            // Determina status code baseado no código da exceção
            $statusCode = $this->getStatusCodeFromException($e);
            // add flash error
            if ($this->flash !== null) {
                $this->flash->addMessage('error', $e->getMessage());
            }

            return $this->jsonResponse($response, [
                'error' => $e->getMessage(),
            ], $statusCode);
        }
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
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'error' => 'Missing required fields',
                'missing_fields' => $missingFields,
            ];
        }

        // Validações adicionais
        if (!is_numeric($data['value']) || $data['value'] <= 0) {
            return ['error' => 'The "value" field must be a number greater than zero'];
        }

        if (!is_numeric($data['payer']) || $data['payer'] <= 0) {
            return ['error' => 'The "payer" field must be a valid ID'];
        }

        if (!is_numeric($data['payee']) || $data['payee'] <= 0) {
            return ['error' => 'The "payee" field must be a valid ID'];
        }

        return null;
    }

    /**
     * Determina status code HTTP baseado na exceção
     */
    private function getStatusCodeFromException(Exception $e): int
    {
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
