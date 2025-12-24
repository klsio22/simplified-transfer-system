<?php

declare(strict_types=1);

namespace App\Core;

use Exception as BaseException;

/**
 * Base exception class with HTTP status code support
 *
 * All application exceptions inherit from this base class
 * to provide consistent error handling and HTTP response mapping.
 */
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

/**
 * Thrown when a transfer-related operation fails
 *
 * This is the base exception for all transfer-specific errors
 */
class TransferException extends AppException
{
}

/**
 * Thrown when a user is not found in the repository
 *
 * Status code: 404 Not Found
 */
class UserNotFoundException extends AppException
{
    public function __construct(string $message = 'User not found', ?BaseException $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

/**
 * Thrown when transfer data is invalid (amount <= 0, same payer/payee, etc.)
 *
 * Status code: 422 Unprocessable Entity
 */
class InvalidTransferException extends AppException
{
    public function __construct(string $message = 'Invalid transfer data', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

/**
 * Thrown when a business rule is violated
 *
 * Examples:
 * - Shopkeeper trying to send a transfer
 * - Insufficient balance
 * - Transfer limit exceeded
 *
 * Status code: 422 Unprocessable Entity
 */
class BusinessRuleException extends AppException
{
    public function __construct(string $message = 'Business rule violation', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

/**
 * Thrown when a transaction is not authorized by the authorization service
 *
 * Status code: 422 Unprocessable Entity
 */
class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Transaction not authorized', ?BaseException $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

/**
 * Thrown when a transfer transaction fails during processing
 *
 * This typically occurs when database operations fail
 *
 * Status code: 500 Internal Server Error
 */
class TransferProcessingException extends AppException
{
    public function __construct(string $message = 'Transfer processing failed', ?BaseException $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
