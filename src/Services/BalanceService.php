<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\InvalidTransferException;
use App\Core\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;

class BalanceService
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getBalance(mixed $id): User
    {
        $userId = (int) $id;

        if ($userId <= 0) {
            throw new InvalidTransferException('User ID must be a positive integer');
        }

        $user = $this->userRepository->find($userId);

        if ($user === null) {
            throw new UserNotFoundException('User not found');
        }

        return $user;
    }
}
