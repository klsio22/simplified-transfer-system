<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_DATABASE'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];

try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Criando banco de dados...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    echo "Criando tabela users...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            cpf_cnpj VARCHAR(18) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            type ENUM('common', 'merchant') NOT NULL DEFAULT 'common',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_cpf_cnpj (cpf_cnpj)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "Criando tabela wallets...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wallets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "Criando tabela transactions...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payer_id INT NOT NULL,
            payee_id INT NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (payer_id) REFERENCES users(id),
            FOREIGN KEY (payee_id) REFERENCES users(id),
            INDEX idx_payer_id (payer_id),
            INDEX idx_payee_id (payee_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "Criando dados de teste (seed)...\n";

    // Criar usuários comuns
    $pdo->exec("
        INSERT INTO users (full_name, cpf_cnpj, email, password, type) VALUES
        ('João Silva', '12345678901', 'joao@example.com', '" . password_hash('senha123', PASSWORD_BCRYPT) . "', 'common'),
        ('Maria Santos', '98765432100', 'maria@example.com', '" . password_hash('senha123', PASSWORD_BCRYPT) . "', 'common')
        ON DUPLICATE KEY UPDATE id=id
    ");

    // Criar lojistas
    $pdo->exec("
        INSERT INTO users (full_name, cpf_cnpj, email, password, type) VALUES
        ('Loja ABC', '12345678000199', 'loja@example.com', '" . password_hash('senha123', PASSWORD_BCRYPT) . "', 'merchant'),
        ('Mercado Central', '98765432000188', 'mercado@example.com', '" . password_hash('senha123', PASSWORD_BCRYPT) . "', 'merchant')
        ON DUPLICATE KEY UPDATE id=id
    ");

    // Criar carteiras
    $pdo->exec("
        INSERT INTO wallets (user_id, balance) VALUES
        (1, 1000.00),
        (2, 500.00),
        (3, 0.00),
        (4, 0.00)
        ON DUPLICATE KEY UPDATE balance=balance
    ");

    echo "✅ Migração e seed concluídos com sucesso!\n";
    echo "\n📊 Dados de teste criados:\n";
    echo "  - Usuário ID 1 (João Silva - common): R$ 1.000,00\n";
    echo "  - Usuário ID 2 (Maria Santos - common): R$ 500,00\n";
    echo "  - Usuário ID 3 (Loja ABC - merchant): R$ 0,00\n";
    echo "  - Usuário ID 4 (Mercado Central - merchant): R$ 0,00\n";
    echo "\n💡 Exemplo de teste:\n";
    echo "  curl -X POST http://localhost:8080/transfer \\\n";
    echo "    -H 'Content-Type: application/json' \\\n";
    echo "    -d '{\"value\": 100.00, \"payer\": 1, \"payee\": 2}'\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
