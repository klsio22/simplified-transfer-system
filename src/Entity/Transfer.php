<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(table: 'transfers')]
class Transfer
{
    #[Column(type: 'primary')]
    private int $id;

    #[Column(type: 'int')]
    private int $payerId;

    #[Column(type: 'int')]
    private int $payeeId;

    #[Column(type: 'decimal(10,2)')]
    private float $value;

    #[Column(type: "string(20)", default: 'pending')]
    private string $status = 'pending';

    #[Column(type: 'timestamp', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[Column(type: 'timestamp', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    // Relations
    #[BelongsTo(target: User::class, outerKey: 'id', innerKey: 'payerId')]
    private ?User $payer = null;

    #[BelongsTo(target: User::class, outerKey: 'id', innerKey: 'payeeId')]
    private ?User $payee = null;

    // ============================================
    // Getters
    // ============================================

    public function getId(): int
    {
        return $this->id;
    }

    public function getPayerId(): int
    {
        return $this->payerId;
    }

    public function getPayer(): ?User
    {
        return $this->payer;
    }

    public function getPayeeId(): int
    {
        return $this->payeeId;
    }

    public function getPayee(): ?User
    {
        return $this->payee;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function setPayerId(int $payerId): self
    {
        $this->payerId = $payerId;
        return $this;
    }

    public function setPayer(User $payer): self
    {
        $this->payer = $payer;
        $this->payerId = $payer->getId();
        return $this;
    }

    public function setPayeeId(int $payeeId): self
    {
        $this->payeeId = $payeeId;
        return $this;
    }

    public function setPayee(User $payee): self
    {
        $this->payee = $payee;
        $this->payeeId = $payee->getId();
        return $this;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    // ============================================
    // Business Logic
    // ============================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function complete(): self
    {
        $this->status = 'completed';
        return $this;
    }

    public function fail(): self
    {
        $this->status = 'failed';
        return $this;
    }

    public function cancel(): self
    {
        $this->status = 'cancelled';
        return $this;
    }
}
