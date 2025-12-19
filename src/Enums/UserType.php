<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case COMMON = 'common';
    case MERCHANT = 'merchant';

    public function canSendTransfer(): bool
    {
        return $this === self::COMMON;
    }

    public function label(): string
    {
        return match ($this) {
            self::COMMON => 'Usuário Comum',
            self::MERCHANT => 'Lojista',
        };
    }
}
