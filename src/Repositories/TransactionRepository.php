<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Transaction;
use PDO;

class TransactionRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findById(int $id): ?Transaction
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function create(Transaction $transaction): Transaction
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (payer_id, payee_id, amount, status, created_at, updated_at)
             VALUES (:payer_id, :payee_id, :amount, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'payer_id' => $transaction->getPayerId(),
            'payee_id' => $transaction->getPayeeId(),
            'amount' => $transaction->getAmount(),
            'status' => $transaction->getStatus(),
        ]);

        $transaction->setId((int) $this->pdo->lastInsertId());

        return $transaction;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE transactions SET status = :status, updated_at = NOW() WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    /**
     * @return Transaction[]
     */
    public function findByPayerId(int $payerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE payer_id = :payer_id ORDER BY created_at DESC');
        $stmt->execute(['payer_id' => $payerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => $this->hydrate($data), $rows);
    }

    /**
     * @return Transaction[]
     */
    public function findByPayeeId(int $payeeId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE payee_id = :payee_id ORDER BY created_at DESC');
        $stmt->execute(['payee_id' => $payeeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => $this->hydrate($data), $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): Transaction
    {
        return new Transaction(
            id: (int) $data['id'],
            payerId: (int) $data['payer_id'],
            payeeId: (int) $data['payee_id'],
            amount: (float) $data['amount'],
            status: $data['status'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at']
        );
    }
}
