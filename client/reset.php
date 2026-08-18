<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$email = $_GET['email'] ?? '';
$error = '';
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $otp      = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $pdo      = medal_pdo();

    if ($password !== $confirm) {
        $error = current_lang() === 'ar' ? 'كلمتا المرور غير متطابقتين.' : 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = current_lang() === 'ar' ? 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.' : 'Password must be at least 6 characters.';
    } elseif ($pdo) {
        $st = $pdo->prepare('SELECT id FROM clients WHERE email = ? AND otp_code = ? AND otp_expires_at > NOW()');
        $st->execute([$email, $otp]);
        $client = $st->fetch();
        if ($client) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE clients SET password_hash = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?')
                ->execute([$hash, $client['id']]);
            $done = true;
        } else {
            $error = current_lang() === 'ar'
                ? 'رمز التحقق غير صحيح أو انتهت صلاحيته.'
                : 'Invalid or expired verification code.';
        }
    }
}

$pageTitle = current_lang() === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Reset Password';
$lang = current_lang();
$dir  = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($lang) ?>" dir="<?= esc($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= esc(t('site_name')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background-color: #f8f5f0; font-family: 'Outfit','Tajawal',sans-serif; padding: 2rem 1rem; }
        .card { width: 100%; max-width: 460px; background: #fff;
                border: 1px solid rgba(212,175,55,.25); border-radius: 24px;
                padding: 3rem; box-shadow: 0 20px 60px rgba(120,100,40,.1); }
        .brand { display: block; text-align: center; font-size: 2rem; font-weight: 800;
                 background: linear-gradient(135deg, #f0dc82, #d4af37);
                 -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        h1 { font-size: 1.2rem; font-weight: 600; text-align: center; margin-bottom: 2rem; }
        .field { margin-bottom: 1.2rem; }
        label { display: block; font-size: .72rem; font-weight: 700;
                letter-spacing: .1em; text-transform: uppercase; color: #8a7318; margin-bottom: .45rem; }
        input { width: 100%; background: #fdfaf5; border: 1px solid rgba(212,175,55,.3);
                border-radius: 10px; padding: .85rem 1rem; font-family: inherit;
                font-size: .95rem; color: #1c1917; outline: none;
                transition: border-color .25s, box-shadow .25s; }
        input:focus { border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,.12); }
        .otp-input { text-align: center; font-size: 1.5rem; letter-spacing: .4rem; }
        .btn-primary { display: block; width: 100%; padding: .95rem; margin-top: 1.5rem;
                       background: linear-gradient(135deg, #f0dc82, #d4af37);
                       color: #1a1508; font-weight: 700; border: none; border-radius: 10px;
                       cursor: pointer; font-size: .88rem; text-transform: uppercase; letter-spacing: .1em; }
        .btn-primary:hover { filter: brightness(1.08); }
        .error-box { background: rgba(239,68,68,.1); color: #dc2626; border-radius: 10px;
                     padding: .75rem; margin-bottom: 1.2rem; text-align: center; }
        .success-box { background: rgba(16,185,129,.1); color: #059669; border-radius: 10px;
                       padding: 1.5rem; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem;
                     font-size: .85rem; color: #8a7318; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <span class="brand"><?= esc(t('site_name')) ?></span>
    <h1><?= esc($pageTitle) ?></h1>

    <?php if ($done): ?>
        <div class="success-box">
            <div style="font-size:2.5rem; margin-bottom:.5rem;">✅</div>
            <strong><?= esc(current_lang() === 'ar' ? 'تم تغيير كلمة المرور بنجاح!' : 'Password changed successfully!') ?></strong>
            <br><a href="login.php" class="back-link"><?= esc(current_lang() === 'ar' ? 'تسجيل الدخول الآن' : 'Login Now') ?></a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error-box"><?= esc($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="email" value="<?= esc($email) ?>">
            <div class="field">
                <label><?= esc(current_lang() === 'ar' ? 'رمز التحقق المرسل على إيميلك' : 'Verification Code (sent to your email)') ?></label>
                <input type="text" name="otp" class="otp-input" maxlength="6" required autofocus placeholder="------">
            </div>
            <div class="field">
                <label><?= esc(t('label_password')) ?></label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="field">
                <label><?= esc(t('label_confirm_password')) ?></label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-primary">
                <?= esc(current_lang() === 'ar' ? 'تغيير كلمة المرور' : 'Change Password') ?>
            </button>
        </form>
        <a href="forgot.php" class="back-link">← <?= esc(current_lang() === 'ar' ? 'إعادة إرسال الرمز' : 'Resend code') ?></a>
    <?php endif; ?>
</div>
</body>
</html>
