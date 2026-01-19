<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mocks;

/**
 * Mock Redis for testing without requiring the Redis extension
 */
class MockRedis
{
    private array $data = [];
    private int $setCallCount = 0;

    public function set($key, $value, $options = null)
    {
        $hasNX = is_array($options) && in_array('NX', $options, true);

        if ($hasNX && isset($this->data[$key])) {
            $this->setCallCount++;
            return false;
        }

        $this->data[$key] = $value;
        $this->setCallCount++;
        return true;
    }

    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function del($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return 1;
        }
        return 0;
    }

    public function exists($key)
    {
        return isset($this->data[$key]) ? 1 : 0;
    }

    public function eval($script, $keys, $numKeys)
    {
        $key = $keys[0];
        $token = $keys[1];

        if (($this->data[$key] ?? null) === $token) {
            unset($this->data[$key]);
            return 1;
        }

        return 0;
    }

    public function getSetCallCount(): int
    {
        return $this->setCallCount;
    }

    public function resetSetCallCount(): void
    {
        $this->setCallCount = 0;
    }
}
