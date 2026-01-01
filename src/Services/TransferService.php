<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessRuleException;
use App\Core\InvalidTransferException;
use App\Core\TransferException;
use App\Core\TransferProcessingException;
use App\Core\UnauthorizedException;
use App\Core\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;

class TransferService
{
    public function __construct(
        private UserRepository $userRepo,
        private AuthorizeService $authorizeService,
        private NotifyService $notifyService
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function transfer(int $payerId, int $payeeId, float $value): array
    {
        $this->validateTransferData($payerId, $payeeId, $value);

        $payer = $this->userRepo->find($payerId);
        $payee = $this->userRepo->find($payeeId);

        if (! $payer) {
            throw new UserNotFoundException('Payer not found');
        }

        if (! $payee) {
            throw new UserNotFoundException('Payee not found');
        }

        $this->validateBusinessRules($payer, $value);

        if (! $this->authorizeService->isAuthorized()) {
            throw new UnauthorizedException('Transaction not authorized by authorization service');
        }

        $this->executeTransfer($payer, $payee, $value);

        $notificationSent = false;

        try {
            $this->notifyService->notify($payeeId);
            $notificationSent = true;
        } catch (TransferException $e) {
            // Notification failed, but transfer completed
            error_log("Failed to notify user {$payeeId}: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Transfer completed successfully',
            'value' => $value,
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'notification_sent' => $notificationSent,
        ];
    }

    /**
     * @param array<string,mixed>|object|null $raw
     * @return array<string,mixed>
     */
    public function processPayload(array|object|null $raw): array
    {
        if ($raw === null) {
            throw new InvalidTransferException('Invalid or empty payload');
        }

        if (is_object($raw)) {
            $raw = (array) $raw;
        } elseif (! is_array($raw)) { // @phpstan-ignore-line
            throw new InvalidTransferException('Invalid payload format');
        }

        $this->validatePayloadFields($raw);

        ['payer' => $payerId, 'payee' => $payeeId, 'value' => $transferValue] = $this->extractAndValidateFields($raw);

        return $this->transfer($payerId, $payeeId, $transferValue);
    }

    /**
     * @param array<string, mixed> $raw
     * @throws InvalidTransferException
     */
    private function validatePayloadFields(array $raw): void
    {
        $required = ['value', 'payer', 'payee'];
        $missing = array_filter($required, fn ($field) => ! array_key_exists($field, $raw));
        if (! empty($missing)) {
            throw new InvalidTransferException('Missing required fields: ' . implode(', ', $missing));
        }
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{payer: int, payee: int, value: float}
     * @throws InvalidTransferException
     */
    private function extractAndValidateFields(array $raw): array
    {
        $value = $raw['value'];
        $payer = $raw['payer'];
        $payee = $raw['payee'];

        if (! is_numeric($value) || (float)$value <= 0) {
            throw new InvalidTransferException('The "value" field must be a number greater than zero');
        }

        if (! is_numeric($payer) || (int)$payer <= 0) {
            throw new InvalidTransferException('The "payer" field must be a valid ID');
        }

        if (! is_numeric($payee) || (int)$payee <= 0) {
            throw new InvalidTransferException('The "payee" field must be a valid ID');
        }

        return [
            'payer' => (int)$payer,
            'payee' => (int)$payee,
            'value' => (float)$value,
        ];
    }

    /**
     * Valida dados básicos da transferência
     */
    private function validateTransferData(int $payerId, int $payeeId, float $value): void
    {
        if ($value <= 0) {
            throw new InvalidTransferException('Transfer value must be greater than zero');
        }

        if ($payerId === $payeeId) {
            throw new InvalidTransferException('Cannot transfer to yourself');
        }
    }

    /**
     * Valida regras de negócio
     */
    private function validateBusinessRules(User $payer, float $value): void
    {
        // Shopkeepers cannot send transfers
        if ($payer->isShopkeeper()) {
            throw new BusinessRuleException('Shopkeepers cannot perform transfers');
        }

        if (! $payer->hasSufficientBalance($value)) {
            throw new BusinessRuleException('Insufficient balance');
        }
    }

    /**
     * Executa a transferência dentro de uma transação DB
     */
    private function executeTransfer(User $payer, User $payee, float $value): void
    {
        $pdo = $this->userRepo->getPdo();

        // Preserve original balances so we can restore in-memory state on failure
        $originalPayerBalance = $payer->balance;
        $originalPayeeBalance = $payee->balance;

        try {
            $pdo->beginTransaction();

            $payer->balance -= $value;
            $this->userRepo->updateBalance($payer);

            $payee->balance += $value;
            $this->userRepo->updateBalance($payee);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable $rollEx) {
                    error_log('Failed to roll back transaction: ' . $rollEx->getMessage());
                }
            }

            $payer->balance = $originalPayerBalance;
            $payee->balance = $originalPayeeBalance;

            error_log("Error during transfer transaction: " . $e->getMessage());

            throw new TransferProcessingException('Failed to process transfer. Please try again.');
        }
    }
}
