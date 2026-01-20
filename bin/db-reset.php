#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';


// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

echo "🔄 Resetting database...\n\n";

try {
    // Get raw PDO connection to MySQL server
    $dbHost = $_ENV['DB_HOST'] ?? 'mysql';
    $dbPort = $_ENV['DB_PORT'] ?? 3306;
    $dbUser = $_ENV['DB_USER'] ?? 'transfer_user';
    $dbPass = $_ENV['DB_PASS'] ?? 'transfer_pass';
    $dbName = $_ENV['DB_NAME'] ?? 'simplified_transfer';

    echo "📡 Connecting to MySQL (host={$dbHost}:{$dbPort})...\n";
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort}",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "  ✓ Connected to MySQL\n\n";

    // Drop and recreate database
    echo "🗑️  Dropping database (if exists)...\n";

    try {
        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        echo "  ✓ Database dropped\n";
    } catch (Exception $e) {
        echo "  ⚠️  Could not drop database: {$e->getMessage()}\n";
    }

    echo "📋 Creating database...\n";
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "  ✓ Database created\n";

    // Switch to new database
    $pdo->exec("USE `{$dbName}`");

    // Load and execute migration
    echo "📋 Running migration...\n";
    $migrationFile = __DIR__ . '/../migrations/01_create_tables.sql';
    $sql = file_get_contents($migrationFile);

    // Split by semicolon and execute each statement (skip comments and empty lines)
    $statements = array_filter(
        array_map(
            fn ($stmt) => preg_replace('/^--.*/m', '', $stmt),  // Remove comments
            array_map('trim', explode(';', $sql))
        ),
        fn ($stmt) => ! empty($stmt) && $stmt !== 'USE simplified_transfer'  // Skip USE statements
    );

    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if (! empty($trimmed)) {
            // Skip SELECT statements in PDO::exec (they cause buffering issues)
            if (strpos(strtoupper($trimmed), 'SELECT') === 0) {
                continue;
            }
            $pdo->exec($trimmed);
        }
    }
    echo "  ✓ Migration executed\n\n";

    // Close raw PDO and initialize ORM
    echo "📦 Initializing ORM schema...\n";
    $pdo = null;  // Close raw connection
    $orm = require __DIR__ . '/../config/orm.php';
    echo "  ✓ ORM initialized and schema synchronized!\n\n";

    // Get DBAL from DatabaseManager
    $dbal = require __DIR__ . '/../config/database.php';
    $default = $dbal->database('default');

    // Verify seeding
    echo "📊 Database seeding summary:\n";
    $result = $default->query("SELECT COUNT(*) as total FROM users")->fetch();
    echo "  ✓ Total users: " . $result['total'] . "\n";

    $result = $default->query("SELECT type, COUNT(*) as count FROM users GROUP BY type")->fetchAll();
    foreach ($result as $row) {
        echo "  ✓ " . ucfirst($row['type']) . "s: " . $row['count'] . "\n";
    }

    echo "\n✅ Database reset complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
