<?php

declare(strict_types=1);

namespace App\Exceptions;

class ExternalServiceException extends AppException
{
    protected int $statusCode = 503;

    public function __construct(string $message = 'Serviço externo indisponível')
    {
        parent::__construct($message);
    }
}
