-- ============================================
-- CRYPTO PAYMENT SYSTEM DATABASE SCHEMA
-- ============================================

-- 1. User Crypto Wallets (Unique Solana addresses per user)
CREATE TABLE `crypto_wallets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `wallet_address` VARCHAR(44) NOT NULL UNIQUE, -- Solana address (44 chars)
    `private_key_encrypted` TEXT NOT NULL, -- AES-256 encrypted private key
    `derivation_path` VARCHAR(100) NOT NULL, -- HD wallet derivation path
    `wallet_type` ENUM('solana', 'usdt_spl') DEFAULT 'solana',
    `status` ENUM('active', 'suspended', 'compromised') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_wallet_address` (`wallet_address`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 2. Crypto Transactions (Comprehensive tracking)
CREATE TABLE `crypto_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `transaction_hash` VARCHAR(88) NOT NULL UNIQUE, -- Solana tx hash
    `wallet_address` VARCHAR(44) NOT NULL,
    `transaction_type` ENUM('deposit', 'withdrawal') NOT NULL,
    `currency` ENUM('SOL', 'USDT') NOT NULL,
    `amount` DECIMAL(20, 9) NOT NULL, -- 9 decimals for SOL precision
    `amount_usd` DECIMAL(15, 2) NOT NULL, -- USD equivalent
    `network_fee` DECIMAL(20, 9) NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'confirmed', 'failed', 'cancelled') DEFAULT 'pending',
    `confirmations` INT DEFAULT 0,
    `required_confirmations` INT DEFAULT 32, -- Solana finality
    `block_height` BIGINT UNSIGNED NULL,
    `from_address` VARCHAR(44) NULL,
    `to_address` VARCHAR(44) NULL,
    `memo` TEXT NULL,
    `processed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_transaction_hash` (`transaction_hash`),
    INDEX `idx_wallet_address` (`wallet_address`),
    INDEX `idx_status` (`status`),
    INDEX `idx_transaction_type` (`transaction_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 3. Deposit Monitoring (Real-time blockchain monitoring)
CREATE TABLE `deposit_monitoring` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wallet_address` VARCHAR(44) NOT NULL,
    `last_processed_signature` VARCHAR(88) NULL,
    `last_processed_block` BIGINT UNSIGNED NULL,
    `monitoring_status` ENUM('active', 'paused', 'error') DEFAULT 'active',
    `last_check_at` TIMESTAMP NULL,
    `error_count` INT DEFAULT 0,
    `last_error` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_wallet` (`wallet_address`),
    INDEX `idx_monitoring_status` (`monitoring_status`)
);

-- 4. Security Audit Trail
CREATE TABLE `crypto_security_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `risk_score` INT DEFAULT 0, -- 0-100 risk assessment
    `security_flags` JSON NULL, -- Fraud detection flags
    `geo_location` JSON NULL, -- Country, city, etc.
    `device_fingerprint` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_risk_score` (`risk_score`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 5. Withdrawal Approval System (Multi-signature for large amounts)
CREATE TABLE `withdrawal_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `crypto_transaction_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(20, 9) NOT NULL,
    `approval_tier` ENUM('auto', 'manual', 'multi_sig') NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    `approved_by` BIGINT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `rejection_reason` TEXT NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_crypto_transaction_id` (`crypto_transaction_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_approval_tier` (`approval_tier`),
    FOREIGN KEY (`crypto_transaction_id`) REFERENCES `crypto_transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 6. Exchange Rate Tracking (Real-time price feeds)
CREATE TABLE `crypto_exchange_rates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `currency_pair` VARCHAR(20) NOT NULL, -- SOL/USD, USDT/USD
    `rate` DECIMAL(20, 8) NOT NULL,
    `source` VARCHAR(50) NOT NULL, -- CoinGecko, Binance, etc.
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_currency_pair` (`currency_pair`),
    INDEX `idx_currency_pair` (`currency_pair`)
);

-- 7. Hot/Cold Wallet Management
CREATE TABLE `system_wallets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wallet_address` VARCHAR(44) NOT NULL UNIQUE,
    `wallet_type` ENUM('hot', 'cold', 'fee_payer') NOT NULL,
    `currency` ENUM('SOL', 'USDT') NOT NULL,
    `balance` DECIMAL(20, 9) NOT NULL DEFAULT 0,
    `threshold_min` DECIMAL(20, 9) NOT NULL DEFAULT 0,
    `threshold_max` DECIMAL(20, 9) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'maintenance', 'compromised') DEFAULT 'active',
    `last_balance_check` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_wallet_type` (`wallet_type`),
    INDEX `idx_currency` (`currency`),
    INDEX `idx_status` (`status`)
);

-- 8. Update existing wallets table for crypto compatibility
ALTER TABLE `wallets` 
ADD COLUMN `sol_balance` DECIMAL(20, 9) DEFAULT 0 AFTER `amount`,
ADD COLUMN `usdt_balance` DECIMAL(20, 9) DEFAULT 0 AFTER `sol_balance`,
ADD COLUMN `total_usd_value` DECIMAL(15, 2) DEFAULT 0 AFTER `usdt_balance`,
ADD COLUMN `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 9. Update transactions table for crypto compatibility
ALTER TABLE `transactions`
ADD COLUMN `crypto_transaction_id` BIGINT UNSIGNED NULL AFTER `transactionno`,
ADD COLUMN `currency_type` ENUM('fiat', 'crypto') DEFAULT 'fiat' AFTER `amount`,
ADD COLUMN `crypto_currency` ENUM('SOL', 'USDT') NULL AFTER `currency_type`,
ADD COLUMN `exchange_rate` DECIMAL(20, 8) NULL AFTER `crypto_currency`,
ADD COLUMN `network_fee` DECIMAL(20, 9) DEFAULT 0 AFTER `exchange_rate`,
ADD INDEX `idx_crypto_transaction_id` (`crypto_transaction_id`),
ADD INDEX `idx_currency_type` (`currency_type`);

-- 10. Security configuration table
CREATE TABLE `crypto_security_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `description` TEXT NULL,
    `is_encrypted` BOOLEAN DEFAULT FALSE,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_setting_key` (`setting_key`)
);

-- Insert default security settings
INSERT INTO `crypto_security_settings` (`setting_key`, `setting_value`, `description`) VALUES
('min_deposit_sol', '0.01', 'Minimum SOL deposit amount'),
('min_deposit_usdt', '5.00', 'Minimum USDT deposit amount'),
('max_withdrawal_sol', '100.00', 'Maximum SOL withdrawal per day'),
('max_withdrawal_usdt', '10000.00', 'Maximum USDT withdrawal per day'),
('auto_approval_limit_usd', '100.00', 'Auto-approve withdrawals under this USD amount'),
('required_confirmations', '32', 'Required Solana confirmations'),
('hot_wallet_threshold', '1000.00', 'Hot wallet maximum balance in USD'),
('withdrawal_fee_sol', '0.001', 'Withdrawal fee in SOL'),
('withdrawal_fee_usdt', '1.00', 'Withdrawal fee in USDT'),
('fraud_detection_enabled', 'true', 'Enable fraud detection algorithms'); 