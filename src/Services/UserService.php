<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\InvalidTransferException;
use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * Cria um novo usuário com validações
     *
     * @param array<string,mixed> $data
     * @return array<string,int|bool>
     * @throws InvalidTransferException
     */
    public function createUser(array $data): array
    {
        // Validate field format and uniqueness
        $this->validateUserData($data);

        // Create and persist user
        $user = $this->buildUserFromData($data);
        $userId = $this->userRepository->create($user);

        return ['success' => true, 'id' => $userId];
    }

    /**
     * Validate user data format and uniqueness constraints
     *
     * @param array<string,mixed> $data
     * @throws InvalidTransferException
     */
    private function validateUserData(array $data): void
    {
        $errors = $this->collectValidationErrors($data);

        if (! empty($errors)) {
            $errorJson = json_encode($errors);
            if ($errorJson === false) {
                $errorJson = 'Validation errors occurred';
            }

            throw new InvalidTransferException($errorJson);
        }

        // Validate uniqueness constraints
        if ($this->userRepository->findByCpf((string)$data['cpf']) !== null) {
            throw new InvalidTransferException('CPF already registered');
        }

        if ($this->userRepository->findByEmail((string)$data['email']) !== null) {
            throw new InvalidTransferException('Email already registered');
        }
    }

    /**
     * Collect validation errors for required fields and formats
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function collectValidationErrors(array $data): array
    {
        $errors = [];
        $required = ['full_name', 'cpf', 'email', 'password', 'type'];

        foreach ($required as $field) {
            if (empty($data[$field]) && empty($data[$this->toCamelCase($field)] ?? null)) {
                $errors[$field] = 'Required field';
            }
        }

        // Validate email format
        if (! empty($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }

        // Validate type field
        if (! empty($data['type']) && ! in_array($data['type'], ['common', 'shopkeeper'], true)) {
            $errors['type'] = 'Invalid type (common|shopkeeper)';
        }

        return $errors;
    }

    /**
     * Build a User object from request data
     *
     * @param array<string,mixed> $data
     */
    private function buildUserFromData(array $data): User
    {
        $user = new User();
        $user->fullName = (string) ($data['fullName'] ?? $data['full_name'] ?? '');
        $user->cpf = (string) $data['cpf'];
        $user->email = (string) $data['email'];
        $user->password = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $user->type = (string) $data['type'];
        $user->balance = isset($data['balance']) ? (float) $data['balance'] : 0.0;

        return $user;
    }

    /**
     * Convert snake_case to camelCase
     */
    private function toCamelCase(string $snakeCase): string
    {
        $parts = explode('_', $snakeCase);
        $camel = array_shift($parts);
        foreach ($parts as $part) {
            $camel .= ucfirst($part);
        }

        return $camel;
    }
}
