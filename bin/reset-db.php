#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db   = $_ENV['DB_NAME'] ?? 'simplified_transfer';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';

echo "Resetting database {$db} on {$host}:{$port}\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Ensure tables exist by running migrations
    echo "Ensuring schema exists (running migrations)...\n";
    passthru("php " . __DIR__ . "/migrate.php", $ret);
    if ($ret !== 0) {
        throw new RuntimeException('migrate.php failed');
    }

    // Disable FK checks, truncate all tables
    echo "Disabling foreign key checks...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . addslashes($db) . "' AND TABLE_TYPE='BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Truncating {$table}...\n";
        $pdo->exec("TRUNCATE TABLE `{$table}`");
    }

    echo "Re-enabling foreign key checks...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "Seeding initial data...\n";

    // Insert seeds using full_name (the canonical column in DB)
    $insert = $pdo->prepare('INSERT INTO users (full_name, cpf, email, password, type, balance) VALUES (?, ?, ?, ?, ?, ?)');
    $insert->execute(['João Silva', '123.456.789-00', 'joao.silva@example.com', password_hash('password', PASSWORD_DEFAULT), 'common', 1000.00]);
    $insert->execute(['Loja ABC Ltda', '12.345.678/0001-90', 'contato@lojaabc.com', password_hash('password', PASSWORD_DEFAULT), 'shopkeeper', 0.00]);

    // Verify data was inserted
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "✅ Database reset complete! Inserted {$count} users.\n";
} catch (Exception $e) {
    echo "❌ Reset error: " . $e->getMessage() . "\n";
    exit(1);
}
