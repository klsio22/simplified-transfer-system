<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public int $id;
    public string $full_name;
    public string $cpf;
    public string $email;
    public string $password;
    public string $type; // 'common' ou 'shopkeeper'
    public float $balance = 0.0;

    /**
     * Verifica se o usuário é lojista
     */
    public function isShopkeeper(): bool
    {
        return $this->type === 'shopkeeper';
    }

    /**
     * Verifica se o usuário é comum
     */
    public function isCommon(): bool
    {
        return $this->type === 'common';
    }

    /**
     * Verifica se o usuário tem saldo suficiente
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    // Getters para uso em controllers e respostas
    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}
