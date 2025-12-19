<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\User;
use App\Enums\UserType;
use PDO;

class UserRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findByCpfCnpj(string $cpfCnpj): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE cpf_cnpj = :cpf_cnpj');
        $stmt->execute(['cpf_cnpj' => $cpfCnpj]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function create(User $user): User
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (full_name, cpf_cnpj, email, password, type, created_at, updated_at) 
             VALUES (:full_name, :cpf_cnpj, :email, :password, :type, NOW(), NOW())'
        );

        $stmt->execute([
            'full_name' => $user->getFullName(),
            'cpf_cnpj' => $user->getCpfCnpj(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'type' => $user->getType()->value,
        ]);

        $user->setId((int) $this->pdo->lastInsertId());

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): User
    {
        return new User(
            id: (int) $data['id'],
            fullName: $data['full_name'],
            cpfCnpj: $data['cpf_cnpj'],
            email: $data['email'],
            password: $data['password'],
            type: UserType::from($data['type']),
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at']
        );
    }
}
