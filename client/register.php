<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = current_lang() === 'ar' ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة.' : 'Session expired. Please refresh the page.';
    }

    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms            = $_POST['agree_terms'] ?? '';

    $old = ['name' => $name, 'email' => $email, 'phone' => $phone];

    if ($terms !== '1') {
        $errors[] = current_lang() === 'ar' ? 'يجب الموافقة على شروط الخدمة.' : 'You must agree to the Terms of Service.';
    }

    if ($name === '') {
        $errors[] = current_lang() === 'ar' ? 'الاسم مطلوب.' : 'Name is required.';
    } elseif (mb_strlen($name) < 2) {
        $errors[] = current_lang() === 'ar' ? 'الاسم يجب أن يكون حرفين على الأقل.' : 'Name must be at least 2 characters.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = current_lang() === 'ar' ? 'الاسم طويل جداً.' : 'Name is too long.';
    }

    if ($email === '') {
        $errors[] = current_lang() === 'ar' ? 'البريد الإلكتروني مطلوب.' : 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = current_lang() === 'ar' ? 'صيغة البريد الإلكتروني غير صحيحة.' : 'Invalid email format.';
    }

    if ($password === '') {
        $errors[] = current_lang() === 'ar' ? 'كلمة المرور مطلوبة.' : 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $errors[] = current_lang() === 'ar' ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.' : 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = current_lang() === 'ar' ? 'كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل.' : 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = current_lang() === 'ar' ? 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل.' : 'Password must contain at least one number.';
    }

    if ($password !== $confirm_password) {
        $errors[] = current_lang() === 'ar' ? 'كلمتا المرور غير متطابقتين.' : 'Passwords do not match.';
    }

    if ($errors === []) {
        $pdo = medal_pdo();
        if ($pdo) {
            $chk = $pdo->prepare('SELECT id FROM clients WHERE email = ?');
            $chk->execute([$email]);

            $chkPhone = $pdo->prepare('SELECT id FROM clients WHERE phone = ? AND phone IS NOT NULL AND phone != ""');
            $chkPhone->execute([$phone]);

            if ($chk->fetch()) {
                $errors[] = current_lang() === 'ar' ? 'البريد الإلكتروني مسجل بالفعل.' : 'Email is already registered.';
            } elseif ($phone !== '' && $chkPhone->fetch()) {
                $errors[] = current_lang() === 'ar' ? 'رقم الهاتف مسجل بالفعل.' : 'Phone number is already registered.';
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $st = $pdo->prepare('INSERT INTO clients (name, email, phone, password_hash, is_verified, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
                    $st->execute([$name, $email, $phone, $hash]);

                    $clientId = (int) $pdo->lastInsertId();

                    $_SESSION['client_id']    = $clientId;
                    $_SESSION['client_name']  = $name;
                    $_SESSION['client_email'] = $email;

                    header('Location: dashboard.php');
                    exit;
                } catch (PDOException $e) {
                    error_log('Error in register.php: ' . $e->getMessage());
                    $errors[] = current_lang() === 'ar' ? 'فشل التسجيل. يرجى المحاولة مرة أخرى.' : 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = t('register_title');
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
        max-width: 560px;
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

    .auth-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    @media (max-width: 540px) {
        .auth-row { grid-template-columns: 1fr; gap: 0; }
    }

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

    .auth-field .field-error {
        color: #e74c3c;
        font-size: 0.78rem;
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .auth-error-list {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        font-size: 0.9rem;
        margin-bottom: 2rem;
    }

    .auth-error-list ul {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .auth-error-list li::before {
        content: "• ";
        color: #e74c3c;
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

    .btn-auth-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .auth-terms {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-top: 1.5rem;
        font-size: 0.85rem;
        color: var(--ink-muted);
    }

    .auth-terms input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--gold);
        margin-top: 2px;
        flex-shrink: 0;
    }

    .auth-terms a {
        color: var(--gold-bright);
        text-decoration: underline;
        font-weight: 600;
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

    .password-strength {
        height: 4px;
        background: var(--border-subtle);
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .password-strength__bar {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s ease, background 0.3s ease;
    }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card">
        <h1><?= esc($pageTitle) ?></h1>

        <?php if ($errors !== []): ?>
            <div class="auth-error-list">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
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

        <form method="POST" id="register-form" novalidate>
            <?= csrf_field() ?>

            <div class="auth-field">
                <label for="name"><?= esc($isAr ? 'الاسم الكامل' : 'Full Name') ?></label>
                <input type="text" id="name" name="name" required autofocus
                       value="<?= esc($old['name']) ?>"
                       placeholder="<?= esc($isAr ? 'أدخل اسمك الكامل' : 'Enter your full name') ?>"
                       minlength="2" maxlength="100">
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="email"><?= esc(t('label_email')) ?></label>
                    <input type="email" id="email" name="email" required
                           value="<?= esc($old['email']) ?>"
                           placeholder="email@example.com">
                </div>
                <div class="auth-field">
                    <label for="phone"><?= esc($isAr ? 'رقم الهاتف' : 'Phone') ?></label>
                    <input type="text" id="phone" name="phone"
                           value="<?= esc($old['phone']) ?>"
                           placeholder="05xxxxxxxx">
                </div>
            </div>

            <div class="auth-row">
                <div class="auth-field">
                    <label for="password"><?= esc(t('label_password')) ?></label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="••••••••" autocomplete="new-password">
                    <div class="password-strength">
                        <div class="password-strength__bar" id="password-strength-bar" style="width:0;"></div>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="confirm_password"><?= esc(t('label_confirm_password')) ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="••••••••" autocomplete="new-password">
                </div>
            </div>

            <div class="auth-terms">
                <input type="checkbox" name="agree_terms" id="agree_terms" value="1" required>
                <label for="agree_terms">
                    <?= $isAr
                        ? 'أوافق على <a href="' . esc(url('terms.php')) . '" target="_blank">شروط الخدمة</a> وسياسة الخصوصية'
                        : 'I agree to the <a href="' . esc(url('terms.php')) . '" target="_blank">Terms of Service</a> and Privacy Policy' ?>
                </label>
            </div>

            <button type="submit" class="btn-auth-primary"><?= esc($isAr ? 'إنشاء حساب' : 'Create Account') ?></button>
        </form>

        <div class="auth-footer">
            <p><?= esc($isAr ? 'لديك حساب بالفعل؟' : 'Already have an account?') ?></p>
            <a href="login.php"><?= esc(t('nav_login')) ?></a>
        </div>
    </div>
</div>

<script>
(function() {
    var pw = document.getElementById('password');
    var bar = document.getElementById('password-strength-bar');

    function updateStrength() {
        var val = pw.value;
        var score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        var colors = ['#e74c3c', '#f39c12', '#f1c40f', '#2ecc71'];
        var width = (score / 4) * 100;

        bar.style.width = width + '%';
        bar.style.background = colors[score] || colors[0];
    }

    pw.addEventListener('input', updateStrength);
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>