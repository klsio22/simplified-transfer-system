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

echo "🔄 Resetting database via Cycle ORM...\n\n";

try {
    // Get ORM
    $orm = require __DIR__ . '/../config/orm.php';

    echo "✓ ORM initialized and schema synchronized!\n\n";

    // Get DBAL from DatabaseManager
    $dbal = require __DIR__ . '/../config/database.php';
    $default = $dbal->database('default');

    // Drop and recreate tables
    echo "🗑️  Clearing tables...\n";
    $tables = ['transfers', 'users'];
    foreach ($tables as $table) {
        try {
            $default->delete($table)->run();
            echo "  ✓ Cleared: {$table}\n";
        } catch (Exception $e) {
            echo "  ⚠️  {$table} not found or error: {$e->getMessage()}\n";
        }
    }

    echo "\n📝 Seeding test users...\n";

    // Create EntityManager
    $em = new EntityManager($orm);

    // Seed users
    $users = [
        [
            'name' => 'João Silva',
            'cpf' => '12345678901',
            'email' => 'joao@test.com',
            'type' => 'common',
            'balance' => 1000.00,
        ],
        [
            'name' => 'Maria Oliveira',
            'cpf' => '98765432100',
            'email' => 'maria@test.com',
            'type' => 'common',
            'balance' => 2000.00,
        ],
        [
            'name' => 'Loja ABC',
            'cpf' => '11111111111',
            'email' => 'loja@test.com',
            'type' => 'shopkeeper',
            'balance' => 5000.00,
        ],
    ];

    foreach ($users as $data) {
        $user = new User();
        $user->setFullName($data['name']);
        $user->setCpf($data['cpf']);
        $user->setEmail($data['email']);
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->setType($data['type']);
        $user->setBalance($data['balance']);
        $user->ensureCreatedAt();

        $em->persist($user)->run();
        echo "  ✓ {$data['name']} ({$data['email']})\n";
    }

    echo "\n✅ Database reset complete!\n";
    echo "   Created 3 test users\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
