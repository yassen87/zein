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
    define('MEDAL_DB_USER', 'root');
}
if (!defined('MEDAL_DB_PASS')) {
    define('MEDAL_DB_PASS', '');
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
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Error in db.php medal_pdo connection: ' . $e->getMessage());
        $failed = true;
        return null;
    }
}

/**
 * Checks if the database has essential tables.
 */
function medal_db_is_initialized(): bool
{
    $pdo = medal_pdo();
    if ($pdo === null) {
        return false;
    }
    try {
        // Check for essential tables
        $pdo->query('SELECT 1 FROM admin_users LIMIT 1');
        $pdo->query('SELECT 1 FROM products LIMIT 1');
        return true;
    } catch (Throwable $e) {
        error_log('Error in db.php medal_db_is_initialized: ' . $e->getMessage());
        return false;
    }
}
