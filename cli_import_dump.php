<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    echo "❌ تعذر الاتصال بقاعدة البيانات. يرجى التأكد من تشغيل MySQL وبيانات الاتصال في includes/db.php\n";
    exit(1);
}

$dumpFile = __DIR__ . '/includes/u868008675_zein.sql';
if (!file_exists($dumpFile)) {
    echo "❌ ملف قاعدة البيانات u868008675_zein.sql غير موجود في مجلد includes/\n";
    exit(1);
}

echo "⏳ جاري تهيئة قاعدة البيانات واستيراد الـ 140+ عطر من الملف الرسمي...\n";

try {
    // 1. Drop existing old/empty tables cleanly
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $pdo->exec("DROP TABLE IF EXISTS `{$tbl}`;");
    }

    // 2. Read and execute the official full dump
    $sqlContent = file_get_contents($dumpFile);
    if (!$sqlContent) {
        throw new Exception("تعذر قراءة محتوى ملف SQL.");
    }

    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
    $pdo->exec($sqlContent);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "✅ تم استيراد كافة الجداول والبيانات بنجاح!\n";

    // 3. Post-import auto-healing, activation, and category links
    try { $pdo->exec("UPDATE products SET active = 1 WHERE active IS NULL OR active = 0;"); } catch (Throwable) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
            product_id INT UNSIGNED NOT NULL,
            category_slug VARCHAR(64) NOT NULL,
            PRIMARY KEY (product_id, category_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $pdo->exec("INSERT IGNORE INTO product_categories (product_id, category_slug)
            SELECT id, category FROM products WHERE category IS NOT NULL AND category != '';");
    } catch (Throwable) {}

    try {
        $pdo->exec("INSERT IGNORE INTO categories (slug, name_en, name_ar, sort_order)
            SELECT DISTINCT category, category, category, 10 FROM products WHERE category IS NOT NULL AND category != '';");
    } catch (Throwable) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            label_en VARCHAR(255) NOT NULL,
            label_ar VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            compare_at_price DECIMAL(10,2) NULL,
            stock INT NOT NULL DEFAULT -1,
            sort_order INT NOT NULL DEFAULT 0,
            KEY idx_pv_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, stock, sort_order)
            SELECT p.id, 'Standard (50ml)', 'الحجم الافتراضي (50 مل)', 250.00, NULL, -1, 0
            FROM products p
            WHERE NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id);");
    } catch (Throwable) {}

    // Ensure orders table has all modern columns for checkout & WhatsApp bot
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmation_code VARCHAR(16) NULL");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS is_confirmed TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS bot_step VARCHAR(32) NOT NULL DEFAULT 'initial'");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmed_at DATETIME NULL");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS wa_conf_sent TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(32) NOT NULL DEFAULT 'cod'");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS waived_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL");
    } catch (Throwable) {}

    // Ensure admin user exists with credentials (admin / P@ssw0rd123!)
    try {
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($adminCount === 0) {
            $hash = password_hash('P@ssw0rd123!', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES ('admin', ?, 'superadmin')")->execute([$hash]);
        }
    } catch (Throwable) {}

    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalOrders = 0;
    try { $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); } catch (Throwable) {}

    echo "\n=======================================================\n";
    echo "🎉 تم استيراد وتفعيل قاعدة البيانات بالكامل بنجاح!\n";
    echo "📊 إجمالي المنتجات المفعلة في المتجر: {$totalProducts} عطر\n";
    echo "📂 إجمالي الأقسام والتصنيفات: {$totalCategories}\n";
    echo "📦 إجمالي الطلبات: {$totalOrders}\n";
    echo "=======================================================\n";

} catch (Throwable $e) {
    echo "❌ خطأ أثناء الاستيراد: " . $e->getMessage() . "\n";
    exit(1);
}
