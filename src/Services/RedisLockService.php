<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\LockAcquisitionException;
use Psr\Log\LoggerInterface;

/**
 * Distributed lock service using Redis
 *
 * Provides pessimistic locking for protecting concurrent access to shared resources.
 * Uses atomic SET NX EX operations and Lua scripts for compare-and-delete semantics.
 *
 * @package App\Services
 */
class RedisLockService
{
    private const LOCK_PREFIX = 'transfer:lock:';
    private const LOCK_TTL = 30;
    private const LOCK_WAIT_TIMEOUT = 5;
    private const LOCK_RETRY_INTERVAL = 50_000;

    public function __construct(
        private mixed $redis,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Acquire locks for two users in deterministic order to prevent deadlocks
     *
     * Always locks the lower ID first, then the higher ID. This ensures that
     * concurrent transfers between the same two users never deadlock (they acquire
     * locks in the same order).
     *
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @return array{lock1: string, lock2: string, id1: int, id2: int} Lock tokens and IDs
     * @throws LockAcquisitionException If unable to acquire locks within timeout
     */
    public function acquireLocks(int $userId1, int $userId2): array
    {
        // Deterministic ordering: always lock in ascending ID order
        $firstId = $userId1 < $userId2 ? $userId1 : $userId2;
        $secondId = $userId1 < $userId2 ? $userId2 : $userId1;

        try {
            $lock1 = $this->acquireLock($firstId);
            $lock2 = $this->acquireLock($secondId);

            return [
                'lock1' => $lock1,
                'lock2' => $lock2,
                'id1' => $firstId,
                'id2' => $secondId,
            ];
        } catch (\Throwable $e) {
            if (isset($lock1)) {
                $this->releaseLock($firstId, $lock1);
            }
            throw $e;
        }
    }

    /**
     * Release both locks sequentially
     *
     * Note: This is NOT atomic. If the first release succeeds but the second fails,
     * one lock remains held until TTL expiration. In practice, this is acceptable
     * because the TTL (30s) ensures eventual cleanup.
     *
     * @param array<string,mixed> $locks Lock tokens from acquireLocks()
     * @return void
     */
    public function releaseLocks(array $locks): void
    {
        $id1 = $locks['id1'];
        $id2 = $locks['id2'];
        $lock1 = $locks['lock1'];
        $lock2 = $locks['lock2'];

        try {
            $this->releaseLock($id1, $lock1);
            $this->releaseLock($id2, $lock2);

            $this->logger?->debug(
                'Locks released for transfer',
                ['user1' => $id1, 'user2' => $id2]
            );
        } catch (\Throwable $e) {
            $this->logger?->warning(
                'Error releasing locks: ' . $e->getMessage(),
                ['user1' => $id1, 'user2' => $id2]
            );
        }
    }

    /**
     * Acquire a single lock with retry logic
     *
     * @param int $userId User ID to lock
     * @return string Lock token (unique identifier for this lock holder)
     * @throws LockAcquisitionException If unable to acquire within timeout
     */
    private function acquireLock(int $userId): string
    {
        $key = self::LOCK_PREFIX . $userId;
        $token = $this->generateToken();
        $startTime = microtime(true);

        while (true) {
            $acquired = $this->redis->set($key, $token, ['NX', 'EX' => self::LOCK_TTL]);

            if ($acquired) {
                $this->logger?->debug("Lock acquired for user {$userId}", ['token' => $token]);

                return $token;
            }

            $elapsed = microtime(true) - $startTime;
            if ($elapsed > self::LOCK_WAIT_TIMEOUT) {
                $this->logger?->error(
                    "Lock acquisition timeout for user {$userId}",
                    ['elapsed' => $elapsed]
                );

                throw new LockAcquisitionException(
                    "Failed to acquire lock for user {$userId} within timeout"
                );
            }

            usleep(self::LOCK_RETRY_INTERVAL);
        }
    }

    /**
     * Release a lock using compare-and-delete Lua script
     *
     * Only deletes the lock if the token matches, preventing one process from
     * releasing a lock held by another.
     *
     * @param int $userId User ID
     * @param string $token Lock token from acquireLock()
     * @return void
     */
    private function releaseLock(int $userId, string $token): void
    {
        $key = self::LOCK_PREFIX . $userId;

        $script = <<<LUA
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
        LUA;

        $result = $this->redis->eval($script, [$key, $token], 1);

        if ($result) {
            $this->logger?->debug("Lock released for user {$userId}", ['token' => $token]);
        } else {
            $this->logger?->warning(
                "Lock release failed - token mismatch for user {$userId}",
                ['token' => $token]
            );
        }
    }

    /**
     * Check if a user has an active lock
     *
     * @param int $userId
     * @return bool
     */
    public function isLocked(int $userId): bool
    {
        $key = self::LOCK_PREFIX . $userId;

        return $this->redis->exists($key) > 0;
    }

    /**
     * Force release a lock (admin emergency operation)
     *
     * @param int $userId
     * @return void
     */
    public function forceReleaseLock(int $userId): void
    {
        $key = self::LOCK_PREFIX . $userId;
        $this->redis->del($key);
        $this->logger?->warning("Lock forcefully released for user {$userId}");
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
