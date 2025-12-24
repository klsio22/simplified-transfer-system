<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use DateTimeImmutable;

#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    /** @var int */
    private int $id = 0;

    #[Column(type: 'string(255)')]
    private string $fullName;

    #[Column(type: 'string(14)', unique: true)]
    private string $cpf;

    #[Column(type: 'string(255)', unique: true)]
    private string $email;

    #[Column(type: 'string(255)')]
    private string $password;

    #[Column(type: "string(20)", default: 'common')]
    private string $type = 'common';

    #[Column(type: 'decimal(10,2)', default: '0.00')]
    private float $balance = 0.00;

    #[Column(type: 'timestamp', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[Column(type: 'timestamp', nullable: true)]
    /** @phpstan-ignore-next-line */
    private ?DateTimeImmutable $updatedAt = null;

    // Relations
    /** @var array<int, Transfer> */
    #[HasMany(target: Transfer::class, outerKey: 'payerId')]
    private array $payedTransfers = [];

    /** @var array<int, Transfer> */
    #[HasMany(target: Transfer::class, outerKey: 'payeeId')]
    private array $receivedTransfers = [];

    /**
     * @return array<int, Transfer>
     */
    public function getPayedTransfers(): array
    {
        return $this->payedTransfers;
    }

    /**
     * @return array<int, Transfer>
     */
    public function getReceivedTransfers(): array
    {
        return $this->receivedTransfers;
    }

    // ============================================
    // Getters
    // ============================================

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ============================================
    // Setters
    // ============================================

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function setCpf(string $cpf): self
    {
        $this->cpf = $cpf;

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    // ============================================
    // Initialization
    // ============================================

    public function ensureCreatedAt(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        }
    }

    // ============================================
    // Business Logic
    // ============================================

    public function isShopkeeper(): bool
    {
        return $this->type === 'shopkeeper';
    }

    public function isCommon(): bool
    {
        return $this->type === 'common';
    }

    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function debit(float $amount): self
    {
        $this->balance -= $amount;

        return $this;
    }

    public function credit(float $amount): self
    {
        $this->balance += $amount;

        return $this;
    }
}
