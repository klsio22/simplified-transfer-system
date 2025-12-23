<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testIsShopkeeperReturnsTrueForShopkeeper(): void
    {
        $user = new User();
        $user->type = 'shopkeeper';

        $this->assertTrue($user->isShopkeeper());
    }

    public function testIsShopkeeperReturnsFalseForCommonUser(): void
    {
        $user = new User();
        $user->type = 'common';

        $this->assertFalse($user->isShopkeeper());
    }

    public function testIsCommonReturnsTrueForCommonUser(): void
    {
        $user = new User();
        $user->type = 'common';

        $this->assertTrue($user->isCommon());
    }

    public function testIsCommonReturnsFalseForShopkeeper(): void
    {
        $user = new User();
        $user->type = 'shopkeeper';

        $this->assertFalse($user->isCommon());
    }

    public function testHasSufficientBalanceReturnsTrueWhenBalanceIsEnough(): void
    {
        $user = new User();
        $user->balance = 100.0;

        $this->assertTrue($user->hasSufficientBalance(50.0));
    }

    public function testHasSufficientBalanceReturnsTrueWhenBalanceIsExact(): void
    {
        $user = new User();
        $user->balance = 100.0;

        $this->assertTrue($user->hasSufficientBalance(100.0));
    }

    public function testHasSufficientBalanceReturnsFalseWhenBalanceIsInsufficient(): void
    {
        $user = new User();
        $user->balance = 50.0;

        $this->assertFalse($user->hasSufficientBalance(100.0));
    }
}
