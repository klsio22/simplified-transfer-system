<?php

declare(strict_types=1);

namespace App\Core;

use Exception as BaseException;

class AppException extends BaseException
{
    protected int $statusCode = 500;

    public function __construct(string $message = '', int $statusCode = 500, ?BaseException $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

class TransferException extends AppException
{
}

class UserNotFoundException extends AppException
{
    public function __construct(string $message = 'User not found', ?BaseException $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

class InvalidTransferException extends AppException
{
    public function __construct(string $message = 'Invalid transfer data', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

class BusinessRuleException extends AppException
{
    public function __construct(string $message = 'Business rule violation', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Transaction not authorized', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

class TransferProcessingException extends AppException
{
    public function __construct(string $message = 'Transfer processing failed', ?BaseException $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
