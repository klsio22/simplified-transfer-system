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
     * @return array<string,int>
     * @throws InvalidTransferException
     */
    public function createUser(array $data): array
    {
        // Validar campos obrigatórios
        $required = ['full_name', 'cpf', 'email', 'password', 'type'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field]) && empty($data[$this->camelCase($field)] ?? null)) {
                $errors[$field] = 'Required field';
            }
        }

        // Validar email
        if (! empty($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }

        // Validar tipo
        if (! empty($data['type']) && ! in_array($data['type'], ['common', 'shopkeeper'], true)) {
            $errors['type'] = 'Invalid type (common|shopkeeper)';
        }

        if (! empty($errors)) {
            throw new InvalidTransferException(json_encode($errors));
        }

        // Validar unicidade de CPF
        if ($this->userRepository->findByCpf($data['cpf']) !== null) {
            throw new InvalidTransferException('CPF already registered');
        }

        // Validar unicidade de email
        if ($this->userRepository->findByEmail($data['email']) !== null) {
            throw new InvalidTransferException('Email already registered');
        }

        // Criar usuário
        $user = new User();
        $user->fullName = (string) ($data['fullName'] ?? $data['full_name'] ?? '');
        $user->cpf = (string) $data['cpf'];
        $user->email = (string) $data['email'];
        $user->password = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $user->type = (string) $data['type'];
        $user->balance = isset($data['balance']) ? (float) $data['balance'] : 0.0;

        $id = $this->userRepository->create($user);

        return ['success' => true, 'id' => $id];
    }

    private function camelCase(string $s): string
    {
        $parts = explode('_', $s);
        $camel = array_shift($parts);
        foreach ($parts as $p) {
            $camel .= ucfirst($p);
        }

        return $camel;
    }
}
