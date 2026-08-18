<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if (is_client_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$email = $_GET['email'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $pdo = medal_pdo();
    if ($pdo) {
        // Lenient check: Allow up to 1 hour for OTP to avoid time sync issues
        $st = $pdo->prepare('SELECT * FROM clients WHERE email = ? AND otp_code = ? AND otp_expires_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $st->execute([$email, $otp]);
        $client = $st->fetch();
        
        if ($client) {
            // Success - Verify and Login
            $upd = $pdo->prepare('UPDATE clients SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?');
            $upd->execute([$client['id']]);
            
            $_SESSION['client_id']   = (int)$client['id'];
            $_SESSION['client_name'] = (string)$client['name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = (current_lang() === 'ar') ? "رمز التحقق غير صحيح أو انتهت صلاحيته" : "Invalid or expired verification code";
        }
    }
}

$pageTitle = (current_lang() === 'ar') ? "التحقق من الحساب" : "Verify Account";
$lang      = current_lang();
$dir       = is_rtl() ? 'rtl' : 'ltr';
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
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f5f0;
            font-family: 'Outfit', 'Tajawal', sans-serif;
            color: #1c1917;
            padding: 2rem 1rem;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid rgba(212,175,55,.25);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(120,100,40,.1);
            text-align: center;
        }
        .brand {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #f0dc82, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; }
        p { color: #78716c; font-size: 0.9rem; margin-bottom: 2rem; }
        .field { margin-bottom: 1.5rem; }
        input {
            width: 100%;
            background: #fdfaf5;
            border: 1px solid rgba(212,175,55,.3);
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            outline: none;
        }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #f0dc82, #d4af37);
            color: #1a1508;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .error-box {
            background: rgba(159,18,57,.15);
            color: #e11d48;
            padding: 0.75rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div style="margin-bottom: 1.5rem; text-align: center;">
            <a href="<?= esc(url('')) ?>">
                <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(t('site_name')) ?>" style="height: 50px; width: auto; object-fit: contain;">
            </a>
        </div>
        <h1><?= esc($pageTitle) ?></h1>
        <p><?= esc(current_lang() === 'ar' ? 'أدخل الرمز المكون من 6 أرقام المرسل إلى ' : 'Enter the 6-digit code sent to ') ?> <strong><?= esc($email) ?></strong></p>

        <?php if ($error): ?>
            <div class="error-box"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <input type="text" name="otp" maxlength="6" required autofocus placeholder="------">
            </div>
            <button type="submit" class="btn-primary"><?= esc(current_lang() === 'ar' ? 'تأكيد' : 'Verify') ?></button>
        </form>
        
        <div style="margin-top: 2rem;">
            <a href="login.php" style="color: #8a7318; text-decoration: none; font-size: 0.9rem;">← <?= esc(current_lang() === 'ar' ? 'العودة لتسجيل الدخول' : 'Back to Login') ?></a>
        </div>
    </div>
</body>
</html>
