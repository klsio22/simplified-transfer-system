<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testCanCreateTransaction(): void
    {
        $transaction = new Transaction(
            id: 1,
            payerId: 1,
            payeeId: 2,
            amount: 100.00,
            status: 'pending'
        );

        $this->assertEquals(1, $transaction->getId());
        $this->assertEquals(1, $transaction->getPayerId());
        $this->assertEquals(2, $transaction->getPayeeId());
        $this->assertEquals(100.00, $transaction->getAmount());
        $this->assertEquals('pending', $transaction->getStatus());
    }

    public function testMarkAsCompleted(): void
    {
        $transaction = new Transaction(
            id: 1,
            payerId: 1,
            payeeId: 2,
            amount: 100.00
        );

        $transaction->markAsCompleted();

        $this->assertEquals('completed', $transaction->getStatus());
    }

    public function testMarkAsFailed(): void
    {
        $transaction = new Transaction(
            id: 1,
            payerId: 1,
            payeeId: 2,
            amount: 100.00
        );

        $transaction->markAsFailed();

        $this->assertEquals('failed', $transaction->getStatus());
    }

    public function testTransactionToArray(): void
    {
        $transaction = new Transaction(
            id: 1,
            payerId: 1,
            payeeId: 2,
            amount: 100.00,
            status: 'completed'
        );

        $array = $transaction->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('payer_id', $array);
        $this->assertArrayHasKey('payee_id', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertEquals('completed', $array['status']);
    }
}
