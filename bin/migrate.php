#!/usr/bin/env php
<?php

declare(strict_types=1);

// Simple migration runner: executes migrations/01_create_tables.sql
require __DIR__ . '/../vendor/autoload.php';

// Load env
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db   = $_ENV['DB_NAME'] ?? 'simplified_transfer';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';

echo "Running migrations SQL against {$host}:{$port}/{$db}\n";

$file = __DIR__ . '/../migrations/01_create_tables.sql';
if (!file_exists($file)) {
    echo "Migration file not found: {$file}\n";
    exit(1);
}

$raw = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Filter out CREATE DATABASE ...; block and USE statements
$filtered = [];
$skip = false;
foreach ($raw as $line) {
    $trim = trim($line);
    // skip comments
    if (str_starts_with($trim, '--') || str_starts_with($trim, '/*')) {
        continue;
    }

    if (!$skip && preg_match('/^CREATE\s+DATABASE/i', $trim)) {
        // begin skipping until semicolon-end
        $skip = true;
        if (str_contains($trim, ';')) {
            $skip = false;
        }
        continue;
    }

    if ($skip) {
        if (str_contains($trim, ';')) {
            $skip = false;
        }
        continue;
    }

    // skip USE statements
    if (preg_match('/^USE\s+/i', $trim)) {
        continue;
    }

    $filtered[] = $line;
}

$sql = implode("\n", $filtered);

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Split statements by semicolon and execute non-empty statements
    $stmts = array_filter(array_map('trim', explode(';', $sql)));

    // Separate CREATE/ALTER statements from INSERT statements (exclude SELECT/verification queries)
    $createStmts = [];
    $insertStmts = [];

    foreach ($stmts as $stmt) {
        if ($stmt === '') {
            continue;
        }

        if (preg_match('/^(CREATE|ALTER|DROP)\s+/i', $stmt)) {
            $createStmts[] = $stmt;
        } elseif (preg_match('/^INSERT\s+/i', $stmt)) {
            $insertStmts[] = $stmt;
        } else {
            // Skip SELECT, verification queries, etc.
            // echo "Skipping non-executable statement: " . substr($stmt, 0, 50) . "...\n";
        }
    }

    // Execute all CREATE/ALTER statements first
    echo "Executing CREATE/ALTER statements...\n";
    foreach ($createStmts as $stmt) {
        $pdo->exec($stmt);
    }

    // Reconnect PDO to clear any query state
    $pdo = null;
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Then check user table columns once - fresh connection
    $colCheck = [];
    try {
        $colCheck = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . addslashes($db) . "' AND TABLE_NAME = 'users'")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // table may not exist, continue anyway
    }

    $hasFullName = in_array('fullName', $colCheck, true);
    $hasFullNameSnake = in_array('full_name', $colCheck, true);

    // Get cpf column info - fresh connection
    $cpfLength = null;
    try {
        $cpfLength = $pdo->query("SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . addslashes($db) . "' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cpf'")->fetchColumn();
    } catch (Exception $e) {
        // ignore
    }

    // If cpf is too small for CNPJ formatting, expand it
    if ($cpfLength !== null && (int)$cpfLength < 32) {
        try {
            echo "Altering users.cpf column to VARCHAR(32) to accommodate CNPJ/CPF formats...\n";
            $pdo->exec("ALTER TABLE `users` MODIFY `cpf` VARCHAR(32) NOT NULL");
        } catch (Exception $e) {
            echo "Warning: failed to alter cpf column: " . $e->getMessage() . "\n";
        }
    }

    // Now process and execute INSERT statements
    echo "Executing INSERT statements...\n";
    foreach ($insertStmts as $stmt) {
        if (preg_match('/^INSERT\s+INTO\s+users\b/i', $stmt)) {
            // Adapt column name if needed - ALWAYS convert fullName to full_name since DB uses full_name
            $stmt = preg_replace('/\bfullName\b/i', 'full_name', $stmt);

            // Make idempotent
            if (!preg_match('/ON\s+DUPLICATE\s+KEY\s+UPDATE/i', $stmt)) {
                $stmt = rtrim($stmt);
                $stmt .= ' ON DUPLICATE KEY UPDATE id = id';
            }
        }

        try {
            $pdo->exec($stmt);
        } catch (Exception $e) {
            echo "Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($stmt, 0, 100) . "...\n";
            throw $e;
        }
    }

    echo "Migrations executed successfully.\n";

    // Post-migration: ensure compatibility by adding fullName column if missing
    try {
        // Use a fresh query for column check
        $colCheckResult = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . addslashes($db) . "' AND TABLE_NAME = 'users'");
        if ($colCheckResult) {
            $colCheck = $colCheckResult->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $colCheck = [];
        }

        $hasFullName = in_array('fullName', $colCheck, true);
        $hasFullNameSnake = in_array('full_name', $colCheck, true);

        if (!$hasFullName && $hasFullNameSnake) {
            echo "Adding compatible column `fullName` (generated from `full_name`)...\n";
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `fullName` VARCHAR(255) GENERATED ALWAYS AS (`full_name`) STORED");
            echo "Column `fullName` created as generated column.\n";
        }
    } catch (PDOException $e) {
        // If column already exists or other non-fatal error, continue
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Compatibility setup info: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
