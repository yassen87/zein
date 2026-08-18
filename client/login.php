<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $error = current_lang() === 'ar' ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة.' : 'Session expired. Please refresh the page.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);
        $oldEmail = $email;

        if ($email === '' || $password === '') {
            $error = current_lang() === 'ar' ? 'يرجى إدخال البريد الإلكتروني وكلمة المرور.' : 'Please enter your email and password.';
        } else {
            $pdo = medal_pdo();
            if ($pdo) {
                $st = $pdo->prepare('SELECT * FROM clients WHERE email = ?');
                $st->execute([$email]);
                $client = $st->fetch();

                if ($client && password_verify($password, $client['password_hash'])) {
                    $_SESSION['client_id']    = (int)$client['id'];
                    $_SESSION['client_name']  = (string)$client['name'];
                    $_SESSION['client_email'] = (string)$client['email'];

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $selector = bin2hex(random_bytes(16));
                        $hashedToken = hash('sha256', $token);
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                        $pdo->prepare('DELETE FROM client_remember_tokens WHERE client_id = ?')->execute([$client['id']]);
                        $pdo->prepare('INSERT INTO client_remember_tokens (client_id, selector, hashed_token, expires_at) VALUES (?, ?, ?, ?)')
                            ->execute([$client['id'], $selector, $hashedToken, $expires]);

                        setcookie('remember_me', $selector . ':' . $token, [
                            'expires' => strtotime('+30 days'),
                            'path' => '/',
                            'domain' => '',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]);
                    }

                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = current_lang() === 'ar'
                        ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                        : 'Invalid email or password.';
                }
            }
        }
    }
}

$pageTitle = t('client_login_title');
$lang = current_lang();
$isAr = $lang === 'ar';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .auth-page-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6rem 1.5rem;
        background: radial-gradient(circle at center, var(--bg-warm), var(--bg));
        min-height: calc(100vh - var(--header-h));
    }

    .auth-card {
        width: 100%;
        max-width: 460px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: clamp(2rem, 5vw, 3.5rem);
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }

    .auth-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--gold-dim), var(--gold-bright), var(--gold-dim));
    }

    .auth-card h1 {
        text-align: center;
        font-family: var(--font-serif);
        font-size: 1.8rem;
        color: var(--ink);
        margin-bottom: 2.5rem;
    }

    .auth-field { margin-bottom: 1.5rem; }

    .auth-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--gold);
        margin-bottom: 0.6rem;
        letter-spacing: 0.05em;
    }

    .auth-field input {
        width: 100%;
        background: var(--bg-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 0.9rem 1.1rem;
        font-family: inherit;
        font-size: 1rem;
        color: var(--ink);
        transition: all 0.3s ease;
    }

    .auth-field input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 4px var(--gold-glow);
        outline: none;
    }

    .auth-field input.input-error {
        border-color: #e74c3c;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15);
    }

    .btn-auth-primary {
        display: block;
        width: 100%;
        margin-top: 2rem;
        padding: 1rem;
        background: linear-gradient(135deg, var(--gold-bright), var(--gold));
        color: #1a1508;
        font-family: inherit;
        font-weight: 700;
        text-transform: uppercase;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-auth-primary:hover {
        filter: brightness(1.1);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px var(--gold-glow);
    }

    .auth-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-radius: 12px;
        padding: 1rem;
        font-size: 0.9rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .auth-remember {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.5rem;
    }

    .auth-remember label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--ink-muted);
        cursor: pointer;
    }

    .auth-remember input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--gold);
    }

    .auth-remember a {
        font-size: 0.85rem;
        color: var(--gold-dim);
        text-decoration: none;
    }

    .auth-remember a:hover {
        text-decoration: underline;
    }

    .auth-footer {
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border-subtle);
        text-align: center;
    }

    .auth-footer p {
        font-size: 0.95rem;
        color: var(--ink-muted);
        margin-bottom: 1rem;
    }

    .auth-footer a {
        color: var(--gold-bright);
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }

    .social-btn:hover { background: #f9f9f9; border-color: #ccc; transform: translateY(-1px); }
    .social-btn--google:hover { border-color: #4285F4; }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card">
        <h1><?= esc($pageTitle) ?></h1>

        <?php if ($error): ?>
            <div class="auth-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php
        $googleClientId = get_setting('google_client_id');
        if ($googleClientId):
        ?>
        <div class="social-login" style="margin-bottom:1.5rem; text-align:center;">
            <p style="color:#888; font-size:0.85rem; margin-bottom:1rem;">أو سجل دخول باستخدام</p>
            <div style="display:flex; gap:1rem; justify-content:center;">
                <a href="<?= esc(url('client/auth/google.php')) ?>" class="social-btn social-btn--google" style="display:flex;align-items:center;gap:0.5rem;padding:0.7rem 1.5rem;border:1px solid #ddd;border-radius:10px;text-decoration:none;color:#333;font-weight:600;font-size:0.9rem;transition:all 0.2s;background:#fff;">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Google
                </a>
            </div>
            <div style="margin:1.5rem 0; position:relative; text-align:center;">
                <span style="background:#fff;padding:0 1rem;color:#bbb;position:relative;z-index:1;">أو</span>
                <hr style="position:absolute;top:50%;width:100%;border:none;border-top:1px solid #eee;margin:0;z-index:0;">
            </div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>

            <div class="auth-field">
                <label for="email"><?= esc(t('label_email')) ?></label>
                <input type="email" id="email" name="email" required autofocus
                       value="<?= esc($oldEmail) ?>"
                       placeholder="email@example.com"
                       class="<?= $error ? 'input-error' : '' ?>">
            </div>
            <div class="auth-field">
                <label for="password"><?= esc(t('label_password')) ?></label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••"
                       class="<?= $error ? 'input-error' : '' ?>">
                <div class="auth-remember">
                    <label>
                        <input type="checkbox" name="remember_me" value="1">
                        <?= esc($isAr ? 'تذكرني' : 'Remember Me') ?>
                    </label>
                    <a href="forgot.php">
                        <?= esc($isAr ? 'نسيت كلمة المرور؟' : 'Forgot Password?') ?>
                    </a>
                </div>
            </div>
            <button type="submit" class="btn-auth-primary"><?= esc($isAr ? 'تسجيل الدخول' : 'Sign In') ?></button>
        </form>

        <div class="auth-footer">
            <p><?= esc($isAr ? 'ليس لديك حساب؟' : 'Don\'t have an account?') ?></p>
            <a href="register.php"><?= esc($isAr ? 'إنشاء حساب جديد' : 'Create Account') ?></a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>