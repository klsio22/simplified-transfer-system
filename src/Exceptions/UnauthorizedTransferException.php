<?php

declare(strict_types=1);

namespace App\Exceptions;

class UnauthorizedTransferException extends AppException
{
    protected int $statusCode = 403;

    public function __construct(string $message = 'Transferência não autorizada')
    {
        parent::__construct($message);
    }
}
