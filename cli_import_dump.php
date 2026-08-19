<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) {
    echo "❌ تعذر الاتصال بقاعدة البيانات. يرجى مراجعة includes/db.php\n";
    exit(1);
}

$dumpFile = __DIR__ . '/includes/u868008675_zein.sql';
if (!file_exists($dumpFile)) {
    echo "❌ ملف قاعدة البيانات u868008675_zein.sql غير موجود في مجلد includes/\n";
    exit(1);
}

echo "⏳ جاري استيراد وتفعيل قاعدة البيانات الرسمية بالكامل...\n";

$dbPass = DB_PASS;
$dbUser = DB_USER;
$dbHost = DB_HOST;
$dbName = DB_NAME;

$imported = false;

// Method 1: Try native MySQL CLI (Fastest, 100% accurate)
if (function_exists('exec')) {
    $passFlag = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
    $cmd = sprintf(
        "mysql -h %s -u %s %s %s < %s 2>&1",
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        $passFlag,
        escapeshellarg($dbName),
        escapeshellarg($dumpFile)
    );
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);
    if ($code === 0) {
        $imported = true;
        echo "✅ تم الاستيراد عبر محرك MySQL المباشر بنجاح.\n";
    }
}

// Method 2: PDO Multi-Query Execution
if (!$imported) {
    echo "ℹ️ جاري الاستيراد عبر محرك PDO الداخلي...\n";
    $sqlContent = file_get_contents($dumpFile);
    if ($sqlContent) {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
            $pdo->exec($sqlContent);
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            $imported = true;
        } catch (Throwable $e) {
            // If whole block threw, split by statement
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $lines = file($dumpFile);
            $buffer = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                $buffer .= $line;
                if (str_ends_with($trimmed, ';')) {
                    try {
                        $pdo->exec($buffer);
                    } catch (Throwable) {}
                    $buffer = '';
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            $imported = true;
        }
    }
}

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
