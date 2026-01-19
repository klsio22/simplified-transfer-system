<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AuthorizationLogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Log an authorization service call
     *
     * @param string $endpoint
     * @param int $httpCode
     * @param array<string,mixed> $request
     * @param string|null $responseBody
     * @param bool $success
     * @return int
     */
    public function log(
        string $endpoint,
        int $httpCode,
        array $request = [],
        ?string $responseBody = null,
        bool $success = false
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO authorization_logs (endpoint, http_code, request_payload, response_body, success, created_at)
             VALUES (:endpoint, :http_code, :request_payload, :response_body, :success, NOW())'
        );

        $stmt->execute([
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'request_payload' => json_encode($request),
            'response_body' => $responseBody,
            'success' => $success ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
