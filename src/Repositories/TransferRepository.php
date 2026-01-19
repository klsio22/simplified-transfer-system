<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entity\Transfer;
use PDO;

class TransferRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Transfer $transfer): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transfers (payer_id, payee_id, value, status, created_at, updated_at)
             VALUES (:payer_id, :payee_id, :value, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'payer_id' => $transfer->getPayerId(),
            'payee_id' => $transfer->getPayeeId(),
            'value' => $transfer->getValue(),
            'status' => $transfer->getStatus(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE transfers SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => $status]);

        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?Transfer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transfers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * @return Transfer[]
     */
    public function findByPayer(int $payerId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transfers WHERE payer_id = :payer_id ORDER BY created_at DESC LIMIT :limit'
        );

        $stmt->bindValue('payer_id', $payerId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transfers = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transfers[] = $this->hydrate($data);
        }

        return $transfers;
    }

    /**
     * @return Transfer[]
     */
    public function findByPayee(int $payeeId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transfers WHERE payee_id = :payee_id ORDER BY created_at DESC LIMIT :limit'
        );

        $stmt->bindValue('payee_id', $payeeId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transfers = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transfers[] = $this->hydrate($data);
        }

        return $transfers;
    }

    /**
     * @return Transfer[]
     */
    public function findByStatus(string $status, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transfers WHERE status = :status ORDER BY created_at DESC LIMIT :limit'
        );

        $stmt->bindValue('status', $status, PDO::PARAM_STR);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transfers = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transfers[] = $this->hydrate($data);
        }

        return $transfers;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hydrate(array $data): Transfer
    {
        $transfer = new Transfer();
        $transfer->setStatus((string) $data['status']);

        return $transfer;
    }
}
