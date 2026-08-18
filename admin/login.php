<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

if (admin_is_logged_in()) {
    header('Location: ' . admin_url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $pdo = medal_pdo();
    if ($pdo === null) {
        $error = t('admin_err_db_not_configured');
    } elseif ($user === '' || $pass === '') {
        $error = t('admin_err_enter_credentials');
    } else {
        $st = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
        $st->execute([$user]);
        $row = $st->fetch();
        if ($row !== false && password_verify($pass, (string) $row['password_hash'])) {
            admin_login((int) $row['id']);
            header('Location: ' . admin_url('index.php'));
            exit;
        }
        $error = t('admin_err_invalid_credentials');
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
    <script>(function(){try{if(localStorage.getItem('medal-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();</script>
    <title><?= esc(t('admin_login_title')) ?> — <?= esc(t('admin_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(admin_asset('assets/css/admin.css?v=' . filemtime(__DIR__ . '/../assets/css/admin.css'))) ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a0a;
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif;
            padding: 1rem;
        }
        .login-bg {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at 50% 0%, rgba(212,175,55,0.08) 0%, transparent 60%),
                        linear-gradient(180deg, #111 0%, #0a0a0a 100%);
            z-index: 0;
        }
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            height: 50px;
            width: auto;
        }
        .login-card {
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-card h1 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .login-card .subtitle {
            color: #888;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: #aaa;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
        }
        .form-group input::placeholder {
            color: #555;
        }
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #d4af37, #c5a059);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
        }
        .login-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #2a2a2a;
        }
        .login-footer a {
            color: #888;
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-footer a:hover {
            color: #d4af37;
        }
        .lang-switch {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .lang-switch a {
            color: #666;
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .lang-switch a.is-current {
            color: #d4af37;
            background: rgba(212,175,55,0.1);
        }
        .theme-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 50%;
            cursor: pointer;
            color: #888;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .theme-toggle-btn:hover {
            color: #d4af37;
            border-color: #d4af37;
        }
    </style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-container">
    <div class="login-logo">
        <img src="<?= esc(admin_asset('assets/img/logo.png')) ?>" alt="Zain Perfumes">
    </div>
    <div class="login-card">
        <h1><?= esc(t('admin_login_heading')) ?></h1>
        <p class="subtitle"><?= current_lang() === 'ar' ? 'لوحة تحكم زين للعطور' : 'Zain Perfumes Admin Panel' ?></p>
        
        <div class="lang-switch">
            <a href="<?= esc(lang_switch_url('en')) ?>"<?= current_lang() === 'en' ? ' class="is-current"' : '' ?>><?= esc(t('lang_en')) ?></a>
            <span style="color:#444;">·</span>
            <a href="<?= esc(lang_switch_url('ar')) ?>"<?= current_lang() === 'ar' ? ' class="is-current"' : '' ?>><?= esc(t('lang_ar')) ?></a>
            <span style="margin:0 0.5rem;color:#444;">|</span>
            <button type="button" class="theme-toggle-btn" id="admin-theme-toggle"
                data-to-daylight="<?= esc(t('theme_to_daylight')) ?>"
                data-to-dark="<?= esc(t('theme_to_dark')) ?>"
                aria-label="<?= esc(t('theme_to_daylight')) ?>" title="<?= esc(t('theme_to_daylight')) ?>">
                <span class="admin-theme-toggle__sun" aria-hidden="true">☀</span>
            </button>
        </div>

        <?php if ($error !== ''): ?>
            <div class="login-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= esc(admin_url('login.php')) ?>">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            
            <div class="form-group">
                <label for="username"><?= esc(t('admin_login_username')) ?></label>
                <input type="text" id="username" name="username" required autocomplete="username" 
                       value="<?= esc(trim((string) ($_POST['username'] ?? ''))) ?>"
                       placeholder="<?= current_lang() === 'ar' ? 'أدخل اسم المستخدم' : 'Enter username' ?>">
            </div>

            <div class="form-group">
                <label for="password"><?= esc(t('admin_login_password')) ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       placeholder="········">
            </div>

            <button type="submit" class="btn-login"><?= esc(t('admin_login_submit')) ?></button>
        </form>

        <div class="login-footer">
            <a href="<?= esc(storefront_url('index.php')) ?>">← <?= current_lang() === 'ar' ? 'العودة للمتجر' : 'Back to Store' ?></a>
            <span style="color:#555;font-size:0.75rem;">Zain Perfumes © <?= date('Y') ?></span>
        </div>
    </div>
</div>
<script src="<?= esc(admin_asset('assets/js/admin-theme.js?v=' . filemtime(__DIR__ . '/../assets/js/admin-theme.js'))) ?>" defer></script>
</body>
</html>