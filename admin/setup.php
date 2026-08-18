<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db_init_helper.php';

// If already initialized, redirect to login
if (medal_db_is_initialized()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string)($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $adminUser = trim((string)($_POST['admin_user'] ?? 'admin'));
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    // 1. Try to update config first
    if (!medal_db_update_config($dbHost, $dbName, $dbUser, $dbPass)) {
        $error = t('setup_err_write');
    } else {
        // Reload config/db to use new credentials
        // Note: Constants cannot be redefined, so we might need a workaround or just try PDO manually
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            
            // 2. Run Schema
            medal_db_run_schema($pdo);
            
            // 3. Run Migrations
            medal_db_run_migrations($pdo, $adminUser, $adminPass);
            
            $success = true;
        } catch (PDOException $e) {
            $error = t('setup_err_conn') . ' (' . $e->getMessage() . ')';
        }
    }
}

$htmlLang = current_lang();
$htmlDir = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(t('setup_title')) ?> — Meda</title>
    <style>
        :root {
            --primary: #d4af37;
            --primary-hover: #b8941f;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #333;
            --muted: #666;
            --error-bg: #fff5f5;
            --error-text: #c53030;
            --success-bg: #f0fff4;
            --success-text: #2f855a;
        }
        .dark {
            --bg: #1a202c;
            --card-bg: #2d3748;
            --text: #f7fafc;
            --muted: #a0aec0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            transition: background-color 0.3s;
        }
        .setup-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin: 20px;
        }
        h1 {
            color: var(--primary);
            margin-top: 0;
            font-size: 1.875rem;
            text-align: center;
        }
        p.lead {
            text-align: center;
            color: var(--muted);
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-sizing: border-box;
            background: var(--card-bg);
            color: var(--text);
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 1rem;
        }
        button:hover {
            background: var(--primary-hover);
        }
        .error-msg {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .success-msg {
            background: var(--success-bg);
            color: var(--success-text);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1><?= esc(t('setup_heading')) ?></h1>
        
        <?php if ($success): ?>
            <div class="success-msg">
                <p><?= esc(t('setup_success')) ?></p>
                <button onclick="window.location.href='login.php'"><?= esc(t('nav_login')) ?></button>
            </div>
        <?php else: ?>
            <p class="lead"><?= esc(t('setup_lead')) ?></p>
            
            <?php if ($error !== ''): ?>
                <div class="error-msg"><?= esc($error) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="db_host"><?= esc(t('setup_db_host')) ?></label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name"><?= esc(t('setup_db_name')) ?></label>
                    <input type="text" id="db_name" name="db_name" placeholder="medal_db" required>
                </div>
                <div class="form-group">
                    <label for="db_user"><?= esc(t('setup_db_user')) ?></label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label for="db_pass"><?= esc(t('setup_db_pass')) ?></label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">
                <div class="form-group">
                    <label for="admin_user"><?= esc(t('setup_admin_user')) ?></label>
                    <input type="text" id="admin_user" name="admin_user" value="admin" required>
                </div>
                <div class="form-group">
                    <label for="admin_pass"><?= esc(t('setup_admin_pass')) ?></label>
                    <input type="password" id="admin_pass" name="admin_pass" value="admin123" required>
                </div>
                
                <button type="submit"><?= esc(t('setup_submit')) ?></button>
            </form>
        <?php endif; ?>
        
        <div class="footer-links">
            <a href="<?= esc(lang_switch_url('en')) ?>">English</a> · 
            <a href="<?= esc(lang_switch_url('ar')) ?>">العربية</a>
        </div>
    </div>
    <script>
        // Simple dark mode detection
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>
