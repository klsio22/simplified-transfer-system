#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cycle\ORM\EntityManager;
use App\Entity\User;

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

echo "🔄 Testing Cycle ORM...\n\n";

try {
    // Get ORM
    $orm = require __DIR__ . '/../config/orm.php';

    echo "✓ ORM initialized successfully!\n";
    echo "✓ Schema compiled and tables synchronized!\n\n";

    // Create EntityManager
    $em = new EntityManager($orm);

    // Test 1: Create a user
    echo "📝 Creating test user...\n";
    $user = new User();
    $user->setFullName('João Silva');
    $user->setCpf('12345678901');
    $user->setEmail('joao@test.com');
    $user->setPassword(password_hash('123456', PASSWORD_BCRYPT));
    $user->setType('common');
    $user->setBalance(1000.00);
    $user->ensureCreatedAt();

    // Persist
    $em->persist($user)->run();

    echo "✅ User created with ID: {$user->getId()}\n";
    echo "   Name: {$user->getFullName()}\n";
    echo "   Balance: R$ {$user->getBalance()}\n\n";

    // Test 2: Fetch user
    echo "🔍 Fetching user by ID...\n";
    $fetchedUser = $orm->getRepository(User::class)->findByPK($user->getId());

    if ($fetchedUser) {
        echo "✅ User fetched!\n";
        echo "   ID: {$fetchedUser->getId()}\n";
        echo "   Name: {$fetchedUser->getFullName()}\n";
        echo "   Email: {$fetchedUser->getEmail()}\n";
        echo "   Type: {$fetchedUser->getType()}\n";
        echo "   Balance: R$ {$fetchedUser->getBalance()}\n\n";
    }

    // Test 3: Update user
    echo "✏️  Updating user balance...\n";
    $fetchedUser->setBalance(1500.50);
    $em->persist($fetchedUser)->run();

    echo "✅ User updated!\n";
    echo "   New Balance: R$ {$fetchedUser->getBalance()}\n\n";

    // Test 4: List all users
    echo "📋 Listing all users:\n";
    $users = $orm->getRepository(User::class)->findAll();

    foreach ($users as $u) {
        echo "   - {$u->getId()}: {$u->getFullName()} ({$u->getEmail()}) | R$ {$u->getBalance()}\n";
    }

    echo "\n✅ All tests passed! Cycle ORM is working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
