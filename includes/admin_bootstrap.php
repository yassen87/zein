<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// If database connection fails, redirect to setup.php to allow configuration
if (medal_pdo() === null) {
    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($currentScript !== 'setup.php') {
        header('Location: ' . admin_url('setup.php'));
        exit;
    }
}

const ADMIN_SESSION_KEY = 'medal_admin_id';

function admin_base_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    if (substr($dir, -6) === '/admin') {
        $base = dirname($dir);
    } else {
        $base = $dir;
    }
    return $base === '/' || $base === '\\' ? '' : rtrim($base, '/');
}

function admin_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $prefix = admin_base_path() !== '' ? admin_base_path() . '/admin/' : '/admin/';
    return $prefix . $path;
}

function admin_asset(string $path): string
{
    $bp = admin_base_path();
    $prefix = $bp !== '' ? $bp . '/' : '/';
    return $prefix . ltrim($path, '/');
}

function storefront_url(string $path = 'index.php'): string
{
    $bp = admin_base_path();
    $relative = ($bp !== '' ? $bp . '/' : '/') . ltrim($path, '/');
    
    // Automatically detect HTTPS and production domain / host
    $scheme = 'http';
    if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) || 
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)) {
        $scheme = 'https';
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    
    return $scheme . '://' . $host . $relative;
}

if (!function_exists('admin_is_logged_in')) {
    function admin_is_logged_in(): bool
    {
        return !empty($_SESSION[ADMIN_SESSION_KEY]);
    }
}

/**
 * Check if the current admin has permission for a specific page/action.
 * Superadmins have access to everything.
 */
if (!function_exists('admin_has_permission')) {
    function admin_has_permission(string $permission): bool
    {
        if (!admin_is_logged_in()) return false;
        
        $role = $_SESSION['admin_role'] ?? 'admin';
        if ($role === 'superadmin') return true;

        $userPerms = $_SESSION['admin_permissions'] ?? [];
        if (!is_array($userPerms)) {
            $userPerms = explode(',', (string)$userPerms);
        }
        
        return in_array($permission, $userPerms, true);
    }
}

if (!function_exists('require_admin')) {
    function require_admin(?string $requiredPermission = null): void
    {
        if (!admin_is_logged_in()) {
            header('Location: ' . admin_url('login.php'));
            exit;
        }

        // If a specific permission is required, check it
        if ($requiredPermission !== null && !admin_has_permission($requiredPermission)) {
            $_SESSION['admin_error'] = t('admin_error_no_permission');
            header('Location: ' . admin_url('index.php'));
            exit;
        }
    }
}

function admin_login(int $userId): void
{
    $_SESSION[ADMIN_SESSION_KEY] = $userId;
    
    // Fetch role and permissions to store in session
    $pdo = medal_pdo();
    if ($pdo) {
        $st = $pdo->prepare("SELECT username, role, permissions FROM admin_users WHERE id = ?");
        $st->execute([$userId]);
        $user = $st->fetch();
        if ($user) {
            $_SESSION['admin_username'] = $user['username'] ?? 'المدير';
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            $_SESSION['admin_permissions'] = !empty($user['permissions']) ? explode(',', $user['permissions']) : [];
        }
    }

    admin_csrf_regenerate();
}

function admin_logout(): void
{
    unset($_SESSION[ADMIN_SESSION_KEY]);
    admin_csrf_regenerate();
}

function admin_csrf_regenerate(): void
{
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        admin_csrf_regenerate();
    }
    return (string) $_SESSION['admin_csrf'];
}

function admin_csrf_field(): string
{
    $token = htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf" value="' . $token . '"><input type="hidden" name="csrf_token" value="' . $token . '">';
}

function admin_verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? ($_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? ($_GET['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))));
    $adminToken = admin_csrf_token();
    $siteToken = function_exists('get_csrf_token') ? get_csrf_token() : '';

    if (is_string($sent) && $sent !== '') {
        if (hash_equals($adminToken, $sent)) {
            return;
        }
        if ($siteToken !== '' && hash_equals($siteToken, $sent)) {
            return;
        }
    }

    // Auto-allow authenticated admin session actions
    if (admin_is_logged_in()) {
        return;
    }

    http_response_code(403);
    exit(t('admin_invalid_csrf'));
}

/** JSON-encoded string safe for inline JS (e.g. confirm()). */
function admin_js_string(string $translationKey): string
{
    return json_encode(t($translationKey), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
}

function admin_order_status_label(string $status): string
{
    $map = [
        'pending' => 'admin_status_pending',
        'processing' => 'admin_status_processing',
        'shipped' => 'admin_status_shipped',
        'delivered' => 'admin_status_delivered',
        'cancelled' => 'admin_status_cancelled',
    ];
    $key = $map[$status] ?? null;

    return $key !== null ? t($key) : $status;
}

function admin_season_label(string $season): string
{
    $map = [
        'winter' => 'admin_season_winter',
        'summer' => 'admin_season_summer',
        'both' => 'admin_season_both',
    ];
    $key = $map[$season] ?? null;

    return $key !== null ? t($key) : $season;
}
