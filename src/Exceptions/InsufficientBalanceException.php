<?php

declare(strict_types=1);

namespace App\Exceptions;

class InsufficientBalanceException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(string $message = 'Saldo insuficiente para realizar a transferência')
    {
        parent::__construct($message);
    }
}
