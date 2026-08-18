<?php
declare(strict_types=1);

/**
 * Zein Perfumes - 1-Click Admin Access & Password Reset
 * Open: https://zeinperfumes.com/admin/quick_admin.php
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

$pdo = medal_pdo();
$msg = '';
$success = false;

if ($pdo === null) {
    $msg = '❌ تعذر الاتصال بقاعدة البيانات. يرجى التأكد من تشغيل MySQL.';
} else {
    // Ensure admin_users table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim((string)($_POST['username'] ?? 'admin'));
        $password = (string)($_POST['password'] ?? 'admin123');

        if ($username !== '' && $password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $pdo->prepare("
                INSERT INTO admin_users (username, password_hash) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
            ");
            $st->execute([$username, $hash]);

            // Fetch admin id
            $gst = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
            $gst->execute([$username]);
            $adminId = (int)($gst->fetch()['id'] ?? 1);

            // Log in immediately
            admin_login($adminId);

            header('Location: ' . admin_url('index.php'));
            exit;
        } else {
            $msg = '⚠️ يرجى كتابة اسم المستخدم وكلمة المرور.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين حساب الأدمن والدخول السريع — زين للعطور</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
            box-sizing: border-box;
        }
        .box {
            background: #ffffff;
            color: #0f172a;
            max-width: 440px;
            width: 100%;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: center;
            border: 2px solid #d4af37;
        }
        .title {
            font-size: 1.45rem;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
        }
        .subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 1.75rem;
            line-height: 1.4;
        }
        .field {
            text-align: right;
            margin-bottom: 1.25rem;
        }
        .label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: #334155;
        }
        .input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.85rem 1rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
        }
        .input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.2);
        }
        .btn {
            background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
            color: #ffffff;
            border: none;
            padding: 0.95rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 800;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(212,175,55,0.4);
            margin-top: 0.75rem;
            transition: all 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212,175,55,0.5);
        }
        .alert {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="box">
    <div style="font-size:2.5rem; margin-bottom:0.5rem;">👑</div>
    <h1 class="title">تعيين الأدمن والدخول الفوري</h1>
    <p class="subtitle">اكتب اسم المستخدم وكلمة المرور واضغط على الزر لتعيينها والدخول للوحة التحكم مباشرة بضغطة زر واحدة!</p>

    <?php if ($msg !== ''): ?>
        <div class="alert"><?= esc($msg) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="field">
            <label class="label" for="username">اسم المستخدم (Username)</label>
            <input type="text" id="username" name="username" class="input" required value="admin">
        </div>

        <div class="field">
            <label class="label" for="password">كلمة المرور الجديدة (Password)</label>
            <input type="text" id="password" name="password" class="input" required value="admin123">
        </div>

        <button type="submit" class="btn">
            🚀 تعيين الحساب والدخول للوحة التحكم الآن
        </button>
    </form>
</div>

</body>
</html>
