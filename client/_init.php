<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/locale.php';
require_once dirname(__DIR__) . '/includes/translations.php';
require_once dirname(__DIR__) . '/includes/product_translations.php';

if (!function_exists('is_client_logged_in')) {
    function is_client_logged_in(): bool {
        return isset($_SESSION['client_id']);
    }
}

if (!function_exists('require_client')) {
    function require_client(): void {
        if (!is_client_logged_in()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('client_id')) {
    function client_id(): int {
        return (int)($_SESSION['client_id'] ?? 0);
    }
}

if (!function_exists('client_name')) {
    function client_name(): string {
        return (string)($_SESSION['client_name'] ?? '');
    }
}

if (!function_exists('client_email')) {
    function client_email(): string {
        return (string)($_SESSION['client_email'] ?? '');
    }
}

$pdo = medal_pdo();
if ($pdo && !isset($_SESSION['_migrated_client_social'])) {
    try {
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS social_id VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS social_provider VARCHAR(20) DEFAULT NULL");
    } catch (Throwable $e) {
        error_log('client _init social migration: ' . $e->getMessage());
    }
    $_SESSION['_migrated_client_social'] = true;
}
