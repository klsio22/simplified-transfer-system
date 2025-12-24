<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\InvalidTransferException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->mockRepository);
    }

    public function testCreateUserSuccessfully(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'securepass123',
            'type' => 'common',
            'balance' => 100.00,
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByCpf')
            ->with('12345678900')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByEmail')
            ->with('john@example.com')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('create')
            ->willReturn(10);

        $result = $this->userService->createUser($userData);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['id']);
    }

    public function testCreateUserThrowsExceptionForMissingFullName(): void
    {
        $userData = [
            'cpf' => '12345678900',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessageMatches('/full_name.*Required field/');

        $this->userService->createUser($userData);
    }

    public function testCreateUserThrowsExceptionForMissingCpf(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessageMatches('/cpf.*Required field/');

        $this->userService->createUser($userData);
    }

    public function testCreateUserThrowsExceptionForInvalidEmail(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'invalid-email',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessageMatches('/email.*Invalid email/');

        $this->userService->createUser($userData);
    }

    public function testCreateUserThrowsExceptionForInvalidType(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'pass123',
            'type' => 'invalid_type',
        ];

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessageMatches('/type.*Invalid type/');

        $this->userService->createUser($userData);
    }

    public function testCreateUserThrowsExceptionForDuplicateCpf(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $existingUser = new User();
        $existingUser->id = 1;
        $existingUser->cpf = '12345678900';

        $this->mockRepository->expects($this->once())
            ->method('findByCpf')
            ->with('12345678900')
            ->willReturn($existingUser);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('CPF already registered');

        $this->userService->createUser($userData);
    }

    public function testCreateUserThrowsExceptionForDuplicateEmail(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cpf' => '12345678900',
            'email' => 'john@example.com',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $existingUser = new User();
        $existingUser->id = 1;
        $existingUser->email = 'john@example.com';

        $this->mockRepository->expects($this->once())
            ->method('findByCpf')
            ->with('12345678900')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByEmail')
            ->with('john@example.com')
            ->willReturn($existingUser);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Email already registered');

        $this->userService->createUser($userData);
    }

    public function testCreateUserWithCamelCaseFields(): void
    {
        $userData = [
            'fullName' => 'Jane Doe',
            'cpf' => '98765432100',
            'email' => 'jane@example.com',
            'password' => 'securepass456',
            'type' => 'shopkeeper',
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByCpf')
            ->with('98765432100')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByEmail')
            ->with('jane@example.com')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('create')
            ->willReturn(20);

        $result = $this->userService->createUser($userData);

        $this->assertTrue($result['success']);
        $this->assertEquals(20, $result['id']);
    }

    public function testCreateUserDefaultsBalanceToZero(): void
    {
        $userData = [
            'full_name' => 'Test User',
            'cpf' => '11122233344',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'type' => 'common',
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByCpf')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (User $user) {
                return $user->balance === 0.0;
            }))
            ->willReturn(30);

        $result = $this->userService->createUser($userData);

        $this->assertTrue($result['success']);
    }
}
