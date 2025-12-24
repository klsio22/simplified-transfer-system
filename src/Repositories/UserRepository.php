<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

class UserRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Busca um usuário pelo ID
     */
    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (! $data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Busca um usuário pelo email
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch();

        if (! $data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Busca um usuário pelo CPF
     */
    public function findByCpf(string $cpf): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE cpf = ?");
        $stmt->execute([$cpf]);
        $data = $stmt->fetch();

        if (! $data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Atualiza o saldo de um usuário
     */
    public function updateBalance(User $user): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$user->balance, $user->id]);
    }

    /**
     * Cria um novo usuário
     */
    public function create(User $user): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (full_name, cpf, email, password, type, balance)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $user->fullName,
            $user->cpf,
            $user->email,
            $user->password,
            $user->type,
            $user->balance,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Retorna o PDO para transações
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Hidrata um objeto User a partir dos dados do banco
     */
    /**
     * Hidrata um objeto User a partir dos dados do banco
     *
     * @param array<string,mixed> $data
     */
    private function hydrate(array $data): User
    {
        $user = new User();
        $user->id = (int) $data['id'];
        $user->fullName = (string) ($data['fullName'] ?? $data['full_name'] ?? '');
        $user->cpf = $data['cpf'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->type = $data['type'];
        $user->balance = (float) $data['balance'];

        return $user;
    }
}
