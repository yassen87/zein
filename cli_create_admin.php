<?php
declare(strict_types=1);

/**
 * Zein Perfumes - Admin Account Creator / Password Reset CLI Tool
 * Usage: php cli_create_admin.php [username] [password]
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if ($pdo === null) {
    echo "❌ تعذر الاتصال بقاعدة البيانات. تأكد من بيانات includes/db.php\n";
    exit(1);
}

// Make sure admin_users table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

$hash = password_hash($password, PASSWORD_DEFAULT);

$st = $pdo->prepare("
    INSERT INTO admin_users (username, password_hash) 
    VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
");
$st->execute([$username, $hash]);

echo "====================================================\n";
echo "🎉 تم إنشاء / تعيين كلمة سر حساب الأدمن بنجاح!\n";
echo "====================================================\n";
echo "👤 اسم المستخدم (Username): " . $username . "\n";
echo "🔑 كلمة المرور (Password): " . $password . "\n";
echo "🔗 رابط لوحة التحكم: https://zeinperfumes.com/admin/login.php\n";
echo "====================================================\n";
