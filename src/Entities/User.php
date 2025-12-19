<?php

declare(strict_types=1);

namespace App\Entities;

use App\Enums\UserType;

class User
{
    public function __construct(
        private ?int $id,
        private string $fullName,
        private string $cpfCnpj,
        private string $email,
        private string $password,
        private UserType $type,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getCpfCnpj(): string
    {
        return $this->cpfCnpj;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function isCommon(): bool
    {
        return $this->type === UserType::COMMON;
    }

    public function isMerchant(): bool
    {
        return $this->type === UserType::MERCHANT;
    }

    public function canSendTransfer(): bool
    {
        return $this->type->canSendTransfer();
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'cpf_cnpj' => $this->cpfCnpj,
            'email' => $this->email,
            'type' => $this->type->value,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
