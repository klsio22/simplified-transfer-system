<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\User;
use App\Enums\UserType;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCommonUserCanSendTransfer(): void
    {
        $user = new User(
            id: 1,
            fullName: 'João Silva',
            cpfCnpj: '12345678901',
            email: 'joao@example.com',
            password: 'hashed',
            type: UserType::COMMON
        );

        $this->assertTrue($user->canSendTransfer());
        $this->assertTrue($user->isCommon());
        $this->assertFalse($user->isMerchant());
    }

    public function testMerchantCannotSendTransfer(): void
    {
        $user = new User(
            id: 2,
            fullName: 'Loja do João',
            cpfCnpj: '12345678000199',
            email: 'loja@example.com',
            password: 'hashed',
            type: UserType::MERCHANT
        );

        $this->assertFalse($user->canSendTransfer());
        $this->assertFalse($user->isCommon());
        $this->assertTrue($user->isMerchant());
    }

    public function testUserToArray(): void
    {
        $user = new User(
            id: 1,
            fullName: 'João Silva',
            cpfCnpj: '12345678901',
            email: 'joao@example.com',
            password: 'hashed',
            type: UserType::COMMON
        );

        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('full_name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertEquals('common', $array['type']);
    }
}
