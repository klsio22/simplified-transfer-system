<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\Wallet;
use PHPUnit\Framework\TestCase;

class WalletTest extends TestCase
{
    public function testCanCreateWallet(): void
    {
        $wallet = new Wallet(
            id: 1,
            userId: 1,
            balance: 1000.00
        );

        $this->assertEquals(1, $wallet->getId());
        $this->assertEquals(1, $wallet->getUserId());
        $this->assertEquals(1000.00, $wallet->getBalance());
    }

    public function testHasEnoughBalance(): void
    {
        $wallet = new Wallet(id: 1, userId: 1, balance: 1000.00);

        $this->assertTrue($wallet->hasEnoughBalance(500.00));
        $this->assertTrue($wallet->hasEnoughBalance(1000.00));
        $this->assertFalse($wallet->hasEnoughBalance(1001.00));
    }

    public function testDebitReducesBalance(): void
    {
        $wallet = new Wallet(id: 1, userId: 1, balance: 1000.00);
        $wallet->debit(300.00);

        $this->assertEquals(700.00, $wallet->getBalance());
    }

    public function testDebitThrowsExceptionWhenInsufficientBalance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $wallet = new Wallet(id: 1, userId: 1, balance: 100.00);
        $wallet->debit(200.00);
    }

    public function testCreditIncreasesBalance(): void
    {
        $wallet = new Wallet(id: 1, userId: 1, balance: 1000.00);
        $wallet->credit(500.00);

        $this->assertEquals(1500.00, $wallet->getBalance());
    }
}
