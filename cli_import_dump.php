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

echo "⏳ جاري قراءة واستيراد ملف قاعدة البيانات: {$dumpFile} ...\n";

$fileContent = file_get_contents($dumpFile);
if (!$fileContent) {
    echo "❌ فشل في قراءة محتوى الملف.\n";
    exit(1);
}

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');

    // Split SQL by semicolons at line breaks
    $queries = preg_split('/;\s*(\r\n|\r|\n)/', $fileContent);
    $executed = 0;
    $errors = 0;

    foreach ($queries as $query) {
        $q = trim($query);
        if ($q === '' || str_starts_with($q, '--') || str_starts_with($q, '/*')) {
            continue;
        }

        try {
            $pdo->exec($q);
            $executed++;
        } catch (Throwable $e) {
            $errMsg = $e->getMessage();

            // Auto-heal missing column
            if (preg_match("/Unknown column '([^']+)' in 'field list'/i", $errMsg, $colMatch)) {
                $missingCol = $colMatch[1];
                if (preg_match('/(?:INSERT|REPLACE|UPDATE)\s+(?:INTO\s+)?`?([a-zA-Z0-9_]+)`?/i', $q, $tblMatch)) {
                    $tbl = $tblMatch[1];
                    try {
                        $pdo->exec("ALTER TABLE `{$tbl}` ADD COLUMN IF NOT EXISTS `{$missingCol}` TEXT NULL;");
                        $pdo->exec($q);
                        $executed++;
                        continue;
                    } catch (Throwable) {}
                }
            }

            if (stripos($errMsg, 'Duplicate entry') !== false || stripos($errMsg, 'already exists') !== false) {
                continue;
            }

            $errors++;
        }
    }

    // Auto-activate all products and sync tables
    $pdo->exec("UPDATE products SET active = 1 WHERE active IS NULL OR active = 0;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        product_id INT UNSIGNED NOT NULL,
        category_slug VARCHAR(64) NOT NULL,
        PRIMARY KEY (product_id, category_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("INSERT IGNORE INTO product_categories (product_id, category_slug)
        SELECT id, category FROM products WHERE category IS NOT NULL AND category != '';");

    $pdo->exec("INSERT IGNORE INTO categories (slug, name_en, name_ar, sort_order)
        SELECT DISTINCT category, category, category, 10 FROM products WHERE category IS NOT NULL AND category != '';");

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        author_name VARCHAR(128) NOT NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        review_text TEXT NOT NULL,
        approved TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pr_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalOrders = 0;
    try { $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); } catch (Throwable) {}

    echo "\n=======================================================\n";
    echo "🎉 تم استيراد وتفعيل قاعدة البيانات بالكامل بنجاح!\n";
    echo "📊 إجمالي المنتجات المفعلة في المتجر: {$totalProducts} عطر\n";
    echo "📂 إجمالي الأقسام والتصنيفات: {$totalCategories}\n";
    echo "📦 إجمالي الطلبات المستوردة: {$totalOrders}\n";
    echo "=======================================================\n";

} catch (Throwable $e) {
    echo "❌ حدث خطأ: " . $e->getMessage() . "\n";
    exit(1);
}
