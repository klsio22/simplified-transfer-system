<?php

declare(strict_types=1);

namespace App\Exceptions;

class UserNotFoundException extends AppException
{
    protected int $statusCode = 404;

    public function __construct(string $message = 'Usuário não encontrado')
    {
        parent::__construct($message);
    }
}
