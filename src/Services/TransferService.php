<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Transaction;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\MerchantCannotSendException;
use App\Exceptions\UserNotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use PDO;

class TransferService
{
    public function __construct(
        private PDO $pdo,
        private UserRepository $userRepository,
        private WalletRepository $walletRepository,
        private TransactionRepository $transactionRepository,
        private AuthorizeService $authorizeService,
        private NotifyService $notifyService
    ) {
    }

    /**
     * Executa uma transferência entre usuários
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     * @throws UserNotFoundException
     * @throws MerchantCannotSendException
     * @throws InsufficientBalanceException
     */
    public function transfer(array $data): array
    {
        // 1. Validar campos obrigatórios
        $this->validateInput($data);

        $payerId = (int) $data['payer'];
        $payeeId = (int) $data['payee'];
        $amount = (float) $data['value'];

        // 2. Verificar se payer e payee são diferentes
        if ($payerId === $payeeId) {
            throw new ValidationException('Não é possível transferir para si mesmo');
        }

        // 3. Buscar usuários
        $payer = $this->userRepository->findById($payerId);
        if (!$payer) {
            throw new UserNotFoundException('Pagador não encontrado');
        }

        $payee = $this->userRepository->findById($payeeId);
        if (!$payee) {
            throw new UserNotFoundException('Recebedor não encontrado');
        }

        // 4. Verificar se pagador não é lojista
        if (!$payer->canSendTransfer()) {
            throw new MerchantCannotSendException();
        }

        // 5. Iniciar transação no banco
        $this->pdo->beginTransaction();

        try {
            // 6. Buscar carteiras com lock (FOR UPDATE)
            $payerWallet = $this->walletRepository->findByUserIdForUpdate($payerId);
            $payeeWallet = $this->walletRepository->findByUserIdForUpdate($payeeId);

            if (!$payerWallet || !$payeeWallet) {
                throw new UserNotFoundException('Carteira não encontrada');
            }

            // 7. Verificar saldo
            if (!$payerWallet->hasEnoughBalance($amount)) {
                throw new InsufficientBalanceException();
            }

            // 8. Consultar serviço autorizador
            $this->authorizeService->authorize();

            // 9. Criar registro da transação
            $transaction = new Transaction(
                id: null,
                payerId: $payerId,
                payeeId: $payeeId,
                amount: $amount,
                status: 'pending'
            );
            $transaction = $this->transactionRepository->create($transaction);

            // 10. Debitar do pagador
            $this->walletRepository->debit($payerId, $amount);

            // 11. Creditar no recebedor
            $this->walletRepository->credit($payeeId, $amount);

            // 12. Atualizar status da transação
            $this->transactionRepository->updateStatus($transaction->getId(), 'completed');
            $transaction->markAsCompleted();

            // 13. Commit da transação
            $this->pdo->commit();

            // 14. Enfileirar notificação (assíncrono)
            $this->notifyService->enqueue([
                'transaction_id' => $transaction->getId(),
                'payer' => $payer->toArray(),
                'payee' => $payee->toArray(),
                'amount' => $amount,
                'message' => sprintf(
                    'Você recebeu uma transferência de R$ %.2f de %s',
                    $amount,
                    $payer->getFullName()
                ),
            ]);

            return [
                'success' => true,
                'message' => 'Transferência realizada com sucesso',
                'transaction' => $transaction->toArray(),
            ];
        } catch (\Throwable $e) {
            // Rollback em caso de qualquer erro
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Valida os campos de entrada
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    private function validateInput(array $data): void
    {
        $errors = [];

        if (!isset($data['value']) || !is_numeric($data['value'])) {
            $errors['value'] = 'O valor é obrigatório e deve ser numérico';
        } elseif ((float) $data['value'] <= 0) {
            $errors['value'] = 'O valor deve ser maior que zero';
        }

        if (!isset($data['payer']) || !is_numeric($data['payer'])) {
            $errors['payer'] = 'O pagador é obrigatório';
        }

        if (!isset($data['payee']) || !is_numeric($data['payee'])) {
            $errors['payee'] = 'O recebedor é obrigatório';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos', $errors);
        }
    }
}
