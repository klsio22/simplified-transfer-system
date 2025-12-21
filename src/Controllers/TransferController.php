<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\TransferService;
use Exception;

class TransferController
{
    public function __construct(
        private TransferService $transferService
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

        try {
            // Executa transferência
            $this->transferService->transfer(
                (int) $data['payer'],
                (int) $data['payee'],
                (float) $data['value']
            );

            return $this->jsonResponse($response, [
                'message' => 'Transferência realizada com sucesso',
            ], 200);
        } catch (Exception $e) {
            // Determina status code baseado no código da exceção
            $statusCode = $this->getStatusCodeFromException($e);
            
            return $this->jsonResponse($response, [
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Valida o payload da requisição
     */
    private function validatePayload(?array $data): ?array
    {
        if ($data === null) {
            return ['error' => 'Payload inválido ou vazio'];
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
                'error' => 'Campos obrigatórios ausentes',
                'missing_fields' => $missingFields,
            ];
        }

        // Validações adicionais
        if (!is_numeric($data['value']) || $data['value'] <= 0) {
            return ['error' => 'O campo "value" deve ser um número maior que zero'];
        }

        if (!is_numeric($data['payer']) || $data['payer'] <= 0) {
            return ['error' => 'O campo "payer" deve ser um ID válido'];
        }

        if (!is_numeric($data['payee']) || $data['payee'] <= 0) {
            return ['error' => 'O campo "payee" deve ser um ID válido'];
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
    private function jsonResponse(Response $response, array $data, int $statusCode): Response
    {
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
