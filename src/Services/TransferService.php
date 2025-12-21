<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use PDOException;
use Exception;

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
     * @throws Exception Se a transferência falhar por qualquer motivo
     */
    public function transfer(int $payerId, int $payeeId, float $value): void
    {
        // 1. Validações básicas
        $this->validateTransferData($payerId, $payeeId, $value);

        // 2. Busca usuários
        $payer = $this->userRepo->find($payerId);
        $payee = $this->userRepo->find($payeeId);

        if (!$payer) {
            throw new Exception('Pagador não encontrado', 404);
        }

        if (!$payee) {
            throw new Exception('Recebedor não encontrado', 404);
        }

        // 3. Validações de regras de negócio
        $this->validateBusinessRules($payer, $value);

        // 4. Consulta serviço autorizador externo
        if (!$this->authorizeService->isAuthorized()) {
            throw new Exception('Transação não autorizada pelo serviço autorizador', 422);
        }

        // 5. Executa transferência dentro de transação
        $this->executeTransfer($payer, $payee, $value);

        // 6. Notifica recebedor (assíncrono, fora da transação)
        try {
            $this->notifyService->notify($payeeId);
        } catch (Exception $e) {
            // Notificação falhou, mas transferência foi concluída
            error_log("Falha ao notificar usuário {$payeeId}: " . $e->getMessage());
        }
    }

    /**
     * Valida dados básicos da transferência
     */
    private function validateTransferData(int $payerId, int $payeeId, float $value): void
    {
        if ($value <= 0) {
            throw new Exception('O valor da transferência deve ser maior que zero', 422);
        }

        if ($payerId === $payeeId) {
            throw new Exception('Não é possível transferir para si mesmo', 422);
        }
    }

    /**
     * Valida regras de negócio
     */
    private function validateBusinessRules(User $payer, float $value): void
    {
        // Lojistas não podem enviar transferências
        if ($payer->isShopkeeper()) {
            throw new Exception('Lojistas não podem realizar transferências', 422);
        }

        // Verifica saldo suficiente
        if (!$payer->hasSufficientBalance($value)) {
            throw new Exception('Saldo insuficiente', 422);
        }
    }

    /**
     * Executa a transferência dentro de uma transação DB
     */
    private function executeTransfer(User $payer, User $payee, float $value): void
    {
        $pdo = $this->userRepo->getPdo();

        try {
            $pdo->beginTransaction();

            // Debita do pagador
            $payer->balance -= $value;
            $this->userRepo->updateBalance($payer);

            // Credita ao recebedor
            $payee->balance += $value;
            $this->userRepo->updateBalance($payee);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro na transação de transferência: " . $e->getMessage());
            throw new Exception('Falha ao processar transferência. Tente novamente.', 500);
        }
    }
}
