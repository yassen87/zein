<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pdo   = medal_pdo();
    if ($pdo && $email !== '') {
        $st = $pdo->prepare('SELECT id, name FROM clients WHERE email = ?');
        $st->execute([$email]);
        $client = $st->fetch();
        if ($client) {
            $otp     = (string) rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $pdo->prepare('UPDATE clients SET otp_code = ?, otp_expires_at = ? WHERE id = ?')
                ->execute([$otp, $expires, $client['id']]);
            send_otp_email($email, $otp, 'reset');
            $success = (current_lang() === 'ar')
                ? 'تم إرسال رمز إعادة التعيين إلى إيميلك.'
                : 'A reset code has been sent to your email.';
        } else {
            // Always show success to avoid email enumeration
            $success = (current_lang() === 'ar')
                ? 'إذا كان الإيميل مسجلاً، ستصلك رسالة قريباً.'
                : 'If this email is registered, you will receive a message shortly.';
        }
    }
}

$pageTitle = (current_lang() === 'ar') ? 'نسيت كلمة المرور' : 'Forgot Password';
$lang = current_lang();
$dir  = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($lang) ?>" dir="<?= esc($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> — <?= esc(t('site_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background-color: #f8f5f0; font-family: 'Outfit', 'Tajawal', sans-serif;
               color: #1c1917; padding: 2rem 1rem; }
        .card { width: 100%; max-width: 440px; background: #fff;
                border: 1px solid rgba(212,175,55,.25); border-radius: 24px;
                padding: 3rem; box-shadow: 0 20px 60px rgba(120,100,40,.1); text-align: center; }
        .brand { display: block; font-size: 2rem; font-weight: 800;
                 background: linear-gradient(135deg, #f0dc82, #d4af37);
                 -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                 margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: .5rem; }
        p.sub { color: #78716c; font-size: .9rem; margin-bottom: 2rem; }
        .field { margin-bottom: 1.4rem; text-align: <?= $dir === 'rtl' ? 'right' : 'left' ?>; }
        label { display: block; font-size: .72rem; font-weight: 700;
                letter-spacing: .1em; text-transform: uppercase; color: #8a7318; margin-bottom: .45rem; }
        input { width: 100%; background: #fdfaf5; border: 1px solid rgba(212,175,55,.3);
                border-radius: 10px; padding: .85rem 1rem; font-family: inherit;
                font-size: .95rem; color: #1c1917; outline: none;
                transition: border-color .25s, box-shadow .25s; }
        input:focus { border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,.12); }
        .btn-primary { display: block; width: 100%; padding: .95rem;
                       background: linear-gradient(135deg, #f0dc82, #d4af37);
                       color: #1a1508; font-weight: 700; border: none; border-radius: 10px;
                       cursor: pointer; font-size: .88rem; letter-spacing: .1em;
                       text-transform: uppercase; transition: filter .25s, transform .2s; margin-top: 1rem; }
        .btn-primary:hover { filter: brightness(1.08); transform: translateY(-2px); }
        .error-box { background: rgba(159,18,57,.1); color: #e11d48; border-radius: 10px;
                     padding: .75rem; margin-bottom: 1.2rem; }
        .success-box { background: rgba(16,185,129,.1); color: #059669; border-radius: 10px;
                       padding: .75rem; margin-bottom: 1.2rem; }
        .back-link { display: inline-block; margin-top: 1.5rem; font-size: .85rem;
                     color: #8a7318; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <span class="brand"><?= esc(t('site_name')) ?></span>
    <h1><?= esc($pageTitle) ?></h1>
    <p class="sub"><?= esc(current_lang() === 'ar' ? 'أدخل إيميلك وسنرسل لك رمز لإعادة تعيين كلمة المرور' : 'Enter your email and we\'ll send you a reset code') ?></p>

    <?php if ($error): ?>
        <div class="error-box"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success-box"><?= esc($success) ?>
            <br><a href="reset.php?email=<?= urlencode($_POST['email'] ?? '') ?>" style="color:#059669; font-weight:600;">
                <?= esc(current_lang() === 'ar' ? '← أدخل الرمز' : 'Enter the code →') ?>
            </a>
        </div>
    <?php else: ?>
    <form method="POST">
        <div class="field">
            <label for="email"><?= esc(t('label_email')) ?></label>
            <input type="email" id="email" name="email" required autofocus>
        </div>
        <button type="submit" class="btn-primary">
            <?= esc(current_lang() === 'ar' ? 'إرسال رمز الاستعادة' : 'Send Reset Code') ?>
        </button>
    </form>
    <?php endif; ?>

    <a href="login.php" class="back-link">← <?= esc(current_lang() === 'ar' ? 'العودة لتسجيل الدخول' : 'Back to Login') ?></a>
</div>
</body>
</html>
