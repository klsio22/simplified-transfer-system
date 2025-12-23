#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Cycle\ORM\ORM;
use Cycle\ORM\EntityManager;
use App\Entity\User;

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

echo "🔄 Testing Cycle ORM integration with Slim DI Container...\n\n";

try {
    // Build container exactly like Slim app
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
    $container = $containerBuilder->build();

    echo "✓ Container built successfully!\n\n";

    // Test ORM from container
    echo "📚 Getting ORM from container...\n";
    $orm = $container->get(ORM::class);
    echo "✓ ORM retrieved from DI container\n\n";

    // Test EntityManager from container
    echo "📝 Getting EntityManager from container...\n";
    $em = $container->get(EntityManager::class);
    echo "✓ EntityManager retrieved from DI container\n\n";

    // Test repository access
    echo "🔍 Accessing User repository...\n";
    $repo = $orm->getRepository(User::class);
    $users = $repo->findAll();
    echo "✓ User repository accessible\n";
    echo "   Found " . count($users) . " users in database\n\n";

    // List users
    if (count($users) > 0) {
        echo "📋 Users in database:\n";
        foreach ($users as $user) {
            echo "   - {$user->getId()}: {$user->getFullName()} ({$user->getEmail()}) | Balance: R$ {$user->getBalance()}\n";
        }
    }

    echo "\n✅ Cycle ORM is fully integrated with Slim DI Container!\n";
    echo "   Ready to use in controllers and services.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
