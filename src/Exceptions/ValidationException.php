<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    protected int $statusCode = 422;

    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message = 'Erro de validação',
        private array $errors = []
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }
}
