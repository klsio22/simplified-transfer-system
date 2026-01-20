<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class NotificationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Log a notification attempt
     *
     * @param int $userId
     * @param string $type
     * @param string $status
     * @param array<string,mixed> $meta
     * @return int Inserted id
     */
    public function log(int $userId, string $type, string $status, array $meta = []): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, type, status, meta, created_at) '
            . 'VALUES (:user_id, :type, :status, :meta, NOW())'
        );

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'status' => $status,
            'meta' => json_encode($meta),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
