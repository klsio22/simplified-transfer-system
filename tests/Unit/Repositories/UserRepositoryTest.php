<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    /** @var PDO&\PHPUnit\Framework\MockObject\MockObject */
    private PDO $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPdo = $this->createMock(PDO::class);
        $this->repository = new UserRepository($this->mockPdo);
    }

    public function testFindReturnsUserWhenFound(): void
    {
        $userData = [
            'id' => 1,
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'hashed_password',
            'type' => 'common',
            'balance' => 100.00,
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([1]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE id = ?')
            ->willReturn($stmt);

        $user = $this->repository->find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('John Doe', $user->fullName);
        $this->assertEquals(100.00, $user->balance);
    }

    public function testFindReturnsNullWhenUserNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([999]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE id = ?')
            ->willReturn($stmt);

        $user = $this->repository->find(999);

        $this->assertNull($user);
    }

    public function testFindByEmailReturnsUserWhenFound(): void
    {
        $userData = [
            'id' => 2,
            'full_name' => 'Jane Doe',
            'cpf' => '98765432100',
            'email' => 'jane@example.com',
            'password' => 'hashed_password',
            'type' => 'shopkeeper',
            'balance' => 500.00,
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['jane@example.com']);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE email = ?')
            ->willReturn($stmt);

        $user = $this->repository->findByEmail('jane@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('jane@example.com', $user->email);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['notfound@example.com']);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE email = ?')
            ->willReturn($stmt);

        $user = $this->repository->findByEmail('notfound@example.com');

        $this->assertNull($user);
    }

    public function testFindByCpfReturnsUserWhenFound(): void
    {
        $userData = [
            'id' => 3,
            'full_name' => 'Bob Smith',
            'cpf' => '11122233344',
            'email' => 'bob@example.com',
            'password' => 'hashed_password',
            'type' => 'common',
            'balance' => 200.00,
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['11122233344']);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE cpf = ?')
            ->willReturn($stmt);

        $user = $this->repository->findByCpf('11122233344');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(3, $user->id);
        $this->assertEquals('11122233344', $user->cpf);
    }

    public function testFindByCpfReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['99999999999']);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE cpf = ?')
            ->willReturn($stmt);

        $user = $this->repository->findByCpf('99999999999');

        $this->assertNull($user);
    }

    public function testUpdateBalanceExecutesCorrectly(): void
    {
        $user = new User();
        $user->id = 5;
        $user->balance = 350.75;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([350.75, 5]);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with('UPDATE users SET balance = ? WHERE id = ?')
            ->willReturn($stmt);

        $this->repository->updateBalance($user);
    }

    public function testCreateInsertsUserAndReturnsId(): void
    {
        $user = new User();
        $user->fullName = 'New User';
        $user->cpf = '55566677788';
        $user->email = 'newuser@example.com';
        $user->password = 'hashed_password';
        $user->type = 'common';
        $user->balance = 0.0;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([
                'New User',
                '55566677788',
                'newuser@example.com',
                'hashed_password',
                'common',
                0.0,
            ]);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO users'))
            ->willReturn($stmt);

        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('42');

        $userId = $this->repository->create($user);

        $this->assertEquals(42, $userId);
    }

    public function testGetPdoReturnsCorrectInstance(): void
    {
        $pdo = $this->repository->getPdo();

        $this->assertSame($this->mockPdo, $pdo);
    }
}
