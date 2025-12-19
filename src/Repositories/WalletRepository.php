<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Wallet;
use PDO;

class WalletRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findByUserId(int $userId): ?Wallet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findByUserIdForUpdate(int $userId): ?Wallet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function create(Wallet $wallet): Wallet
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, balance, created_at, updated_at) 
             VALUES (:user_id, :balance, NOW(), NOW())'
        );

        $stmt->execute([
            'user_id' => $wallet->getUserId(),
            'balance' => $wallet->getBalance(),
        ]);

        $wallet->setId((int) $this->pdo->lastInsertId());

        return $wallet;
    }

    public function updateBalance(int $userId, float $balance): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets SET balance = :balance, updated_at = NOW() WHERE user_id = :user_id'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'balance' => $balance,
        ]);
    }

    public function debit(int $userId, float $amount): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets SET balance = balance - :amount, updated_at = NOW() 
             WHERE user_id = :user_id AND balance >= :amount'
        );

        $stmt->execute([
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function credit(int $userId, float $amount): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets SET balance = balance + :amount, updated_at = NOW() WHERE user_id = :user_id'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'amount' => $amount,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): Wallet
    {
        return new Wallet(
            id: (int) $data['id'],
            userId: (int) $data['user_id'],
            balance: (float) $data['balance'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at']
        );
    }
}
