<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class AppException extends Exception
{
    protected int $statusCode = 500;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
        ];
    }
}
