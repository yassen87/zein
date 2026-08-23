<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/db.local.php')) {
    require_once __DIR__ . '/db.local.php';
}

if (!defined('MEDAL_DB_DSN') && is_file(__DIR__ . '/db.hostinger.php')) {
    require_once __DIR__ . '/db.hostinger.php';
}

if (!defined('MEDAL_DB_DSN')) {
    define('MEDAL_DB_DSN', 'mysql:host=127.0.0.1;dbname=medal_db;charset=utf8mb4');
}
if (!defined('MEDAL_DB_USER')) {
    define('MEDAL_DB_USER', 'zein');
}
if (!defined('MEDAL_DB_PASS')) {
    define('MEDAL_DB_PASS', 'P@ssw0rd123!');
}

function medal_pdo(): ?PDO
{
    static $pdo = null;
    static $failed = false;
    if ($failed) {
        return null;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(MEDAL_DB_DSN, MEDAL_DB_USER, MEDAL_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Error in db.php medal_pdo connection: ' . $e->getMessage());
        $failed = true;
        return null;
    }
}

/**
 * Safely ensure a column exists in a MySQL table without version-specific syntax errors.
 */
function medal_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $st->execute([$column]);
        if ($st->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
        }
    } catch (Throwable) {}
}

/**
 * Ensures orders and order_items tables have all required columns.
 */
function medal_ensure_orders_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        medal_ensure_column($pdo, 'orders', 'confirmation_code', 'VARCHAR(16) NULL');
        medal_ensure_column($pdo, 'orders', 'is_confirmed', 'TINYINT(1) NOT NULL DEFAULT 0');
        medal_ensure_column($pdo, 'orders', 'bot_step', 'VARCHAR(32) NOT NULL DEFAULT \'initial\'');
        medal_ensure_column($pdo, 'orders', 'confirmed_at', 'DATETIME NULL');
        medal_ensure_column($pdo, 'orders', 'wa_conf_sent', 'TINYINT(1) NOT NULL DEFAULT 0');
        medal_ensure_column($pdo, 'orders', 'payment_method', 'VARCHAR(32) NOT NULL DEFAULT \'cod\'');
        medal_ensure_column($pdo, 'orders', 'payment_status', 'VARCHAR(64) NOT NULL DEFAULT \'pending\'');
        try {
            $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(64) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {}
        medal_ensure_column($pdo, 'orders', 'paid_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00');
        medal_ensure_column($pdo, 'orders', 'waived_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00');
        medal_ensure_column($pdo, 'orders', 'delivered_at', 'DATETIME NULL');
        medal_ensure_column($pdo, 'orders', 'address_landmark', 'TEXT NULL');
        medal_ensure_column($pdo, 'orders', 'admin_notes', 'TEXT NULL');
        medal_ensure_column($pdo, 'orders', 'promo_code', 'VARCHAR(64) NULL');
        medal_ensure_column($pdo, 'orders', 'discount_amount', 'DECIMAL(10,2) NULL');
        medal_ensure_column($pdo, 'orders', 'shipping_cost', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00');
        medal_ensure_column($pdo, 'order_items', 'variant_label_snapshot', 'VARCHAR(255) NULL');
        medal_ensure_column($pdo, 'order_items', 'variant_id', 'INT(10) UNSIGNED NULL');

        medal_ensure_column($pdo, 'orders', 'payment_reference', 'VARCHAR(100) NULL');
        medal_ensure_column($pdo, 'orders', 'payment_receipt', 'VARCHAR(255) NULL');
        medal_ensure_column($pdo, 'orders', 'ocr_status', 'VARCHAR(50) NULL DEFAULT \'none\'');

        // Ensure bank_transactions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `bank_transactions` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `provider` VARCHAR(50) NOT NULL DEFAULT 'unknown',
          `amount` DECIMAL(10,2) NOT NULL,
          `sender_number_or_handle` VARCHAR(100) NULL,
          `reference_id` VARCHAR(100) NOT NULL,
          `raw_message` TEXT NULL,
          `status` ENUM('unmatched', 'matched', 'ignored', 'refunded') NOT NULL DEFAULT 'unmatched',
          `matched_order_id` INT UNSIGNED NULL,
          `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `matched_at` DATETIME NULL,
          `ocr_matched_at` DATETIME NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY `idx_ref_id` (`reference_id`),
          KEY `idx_matched_order` (`matched_order_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Make customer_email nullable
        try {
            $pdo->exec("ALTER TABLE orders MODIFY customer_email VARCHAR(255) NULL DEFAULT ''");
        } catch (Throwable) {}
    } catch (Throwable) {}
    $done = true;
}
