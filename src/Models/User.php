<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public int $id;
    public string $fullName;
    public string $cpf;
    public string $email;
    public string $password;
    public string $type; // 'common' ou 'shopkeeper'
    public float $balance = 0.0;

    public function isShopkeeper(): bool
    {
        return $this->type === 'shopkeeper';
    }

    public function isCommon(): bool
    {
        return $this->type === 'common';
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}
