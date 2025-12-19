<?php

declare(strict_types=1);

namespace App\Exceptions;

class MerchantCannotSendException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(string $message = 'Lojistas não podem enviar transferências')
    {
        parent::__construct($message);
    }
}
