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
     * Realiza transferência entre usuários
     *
     * @param int $payerId ID do pagador (quem envia)
     * @param int $payeeId ID do recebedor (quem recebe)
     * @param float $value Valor a ser transferido
     * @return array<string,mixed> Resultado da transferência com informações de notificação
     * @throws TransferException Se a transferência falhar por qualquer motivo
     */
    public function transfer(int $payerId, int $payeeId, float $value): array
    {
        // 1. Validações básicas
        $this->validateTransferData($payerId, $payeeId, $value);

        // 2. Busca usuários
        $payer = $this->userRepo->find($payerId);
        $payee = $this->userRepo->find($payeeId);

        if (! $payer) {
            throw new UserNotFoundException('Payer not found');
        }

        if (! $payee) {
            throw new UserNotFoundException('Payee not found');
        }

        // 3. Validações de regras de negócio
        $this->validateBusinessRules($payer, $value);

        // 4. Consulta serviço autorizador externo
        if (! $this->authorizeService->isAuthorized()) {
            throw new UnauthorizedException('Transaction not authorized by authorization service');
        }

        // 5. Executa transferência dentro de transação
        $this->executeTransfer($payer, $payee, $value);

        // 6. Notifica recebedor (assíncrono, fora da transação)
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
     * Processa e valida um payload bruto de transferência (usado por controllers)
     *
     * @param array<string,mixed>|object|null $raw
     * @return array<string,mixed>
     * @throws InvalidTransferException|BusinessRuleException|UserNotFoundException|UnauthorizedException|TransferProcessingException
     */
    public function processPayload(array|object|null $raw): array
    {
        if ($raw === null) {
            throw new InvalidTransferException('Invalid or empty payload');
        }

        if (is_object($raw)) {
            $raw = (array) $raw;
        }

        if (! is_array($raw)) {
            throw new InvalidTransferException('Invalid payload format');
        }

        $required = ['value', 'payer', 'payee'];
        $missing = array_filter($required, fn($f) => ! array_key_exists($f, $raw));
        if (! empty($missing)) {
            throw new InvalidTransferException('Missing required fields: ' . implode(', ', $missing));
        }

        $value = $raw['value'];
        $payer = $raw['payer'];
        $payee = $raw['payee'];

        // Validate fields
        if (! is_numeric($value) || (float)$value <= 0) {
            throw new InvalidTransferException('The "value" field must be a number greater than zero');
        }

        if (! is_numeric($payer) || (int)$payer <= 0) {
            throw new InvalidTransferException('The "payer" field must be a valid ID');
        }

        if (! is_numeric($payee) || (int)$payee <= 0) {
            throw new InvalidTransferException('The "payee" field must be a valid ID');
        }

        // Delegate to transfer method (will perform business validations and DB operations)
        return $this->transfer((int)$payer, (int)$payee, (float)$value);
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

        // Verifica saldo suficiente
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

            // Debita do pagador
            $payer->balance -= $value;
            $this->userRepo->updateBalance($payer);

            // Credita ao recebedor
            $payee->balance += $value;
            $this->userRepo->updateBalance($payee);

            $pdo->commit();
        } catch (\Throwable $e) {
            // Ensure DB is rolled back if a transaction is active
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable $rollEx) {
                    error_log('Failed to roll back transaction: ' . $rollEx->getMessage());
                }
            }

            // Restore in-memory balances so caller sees consistent state
            $payer->balance = $originalPayerBalance;
            $payee->balance = $originalPayeeBalance;

            error_log("Error during transfer transaction: " . $e->getMessage());

            throw new TransferProcessingException('Failed to process transfer. Please try again.');
        }
    }
}
