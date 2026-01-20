-- ============================================
-- Simplified Transfer System - Database Schema
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS simplified_transfer
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE simplified_transfer;

-- Create table users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM('common', 'shopkeeper') NOT NULL DEFAULT 'common',
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create transfers table (transaction history)
CREATE TABLE IF NOT EXISTS transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id INT NOT NULL,
    payee_id INT NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_payer (payer_id),
    INDEX idx_payee (payee_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Test Seeds
-- ============================================

-- Common users (can send and receive)
INSERT INTO users (fullName, cpf, email, password, type, balance) VALUES
('João Silva', '12345678900', 'joao.silva@example.com', '$2y$10$YourHashedPasswordHere', 'common', 1000.00),
('Maria Oliveira', '98765432100', 'maria.oliveira@example.com', '$2y$10$YourHashedPasswordHere', 'common', 500.00),
('Pedro Santos', '11122233344', 'pedro.santos@example.com', '$2y$10$YourHashedPasswordHere', 'common', 750.00);

-- Shopkeepers (can only receive)
INSERT INTO users (fullName, cpf, email, password, type, balance) VALUES
('Loja ABC Ltda', '12345678000190', 'contato@lojaabc.com', '$2y$10$YourHashedPasswordHere', 'shopkeeper', 0.00),
('Comércio XYZ ME', '98765432000110', 'vendas@comercioxyz.com', '$2y$10$YourHashedPasswordHere', 'shopkeeper', 150.00);

-- ============================================
-- Verification
-- ============================================

SELECT 'Database created successfully!' as message;
SELECT COUNT(*) as total_users FROM users;
SELECT type, COUNT(*) as count, SUM(balance) as total_balance 
FROM users 
GROUP BY type;
