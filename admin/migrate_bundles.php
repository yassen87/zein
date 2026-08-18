<?php
/**
 * Migration: Multi-category products + Offer Bundles
 * Run this once via browser: /admin/migrate_bundles.php
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';

// Simple auth: must be logged in as admin
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    // Allow if accessed directly with secret key (for initial setup)
    if (($_GET['key'] ?? '') !== 'zain_migrate_2025') {
        http_response_code(403);
        exit('Unauthorized. Add ?key=zain_migrate_2025 to the URL or log in as admin first.');
    }
}

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database not connected.');
}

$log = [];
$errors = [];

function run_sql(PDO $pdo, string $sql, string $desc, array &$log, array &$errors): void {
    try {
        $pdo->exec($sql);
        $log[] = "✅ $desc";
    } catch (Throwable $e) {
        $errors[] = "⚠️ $desc — " . $e->getMessage();
    }
}

// ─── 1. Create product_categories pivot table ───────────────────────────────
run_sql($pdo, "
    CREATE TABLE IF NOT EXISTS product_categories (
        product_id INT(10) UNSIGNED NOT NULL,
        category_slug VARCHAR(100) NOT NULL,
        PRIMARY KEY (product_id, category_slug),
        CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "إنشاء جدول product_categories", $log, $errors);

// ─── 2. Migrate existing single-category values into pivot table ─────────────
try {
    $rows = $pdo->query("SELECT id, category FROM products WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_ASSOC);
    $ins = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_slug) VALUES (?, ?)");
    $migrated = 0;
    foreach ($rows as $r) {
        $ins->execute([(int)$r['id'], (string)$r['category']]);
        $migrated++;
    }
    $log[] = "✅ نقل $migrated منتج من عمود category القديم إلى product_categories";
} catch (Throwable $e) {
    $errors[] = "⚠️ نقل البيانات القديمة — " . $e->getMessage();
}

// ─── 3. Create offer_bundles table ───────────────────────────────────────────
run_sql($pdo, "
    CREATE TABLE IF NOT EXISTS offer_bundles (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name_ar VARCHAR(255) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        description_ar TEXT,
        description_en TEXT,
        image_key VARCHAR(500) DEFAULT '',
        discount_type ENUM('none','percent','fixed') DEFAULT 'none',
        discount_value DECIMAL(10,2) DEFAULT 0.00,
        active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "إنشاء جدول offer_bundles", $log, $errors);

// ─── 4. Create offer_bundle_products pivot ───────────────────────────────────
run_sql($pdo, "
    CREATE TABLE IF NOT EXISTS offer_bundle_products (
        bundle_id INT(10) UNSIGNED NOT NULL,
        product_id INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (bundle_id, product_id),
        CONSTRAINT fk_obp_bundle  FOREIGN KEY (bundle_id)  REFERENCES offer_bundles(id) ON DELETE CASCADE,
        CONSTRAINT fk_obp_product FOREIGN KEY (product_id) REFERENCES products(id)      ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", "إنشاء جدول offer_bundle_products", $log, $errors);

// ─── 5. Migrate existing is_offer products into a default bundle ──────────────
try {
    $offerProds = $pdo->query("SELECT id FROM products WHERE is_offer = 1")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($offerProds)) {
        // Check if migration bundle already exists
        $existing = $pdo->query("SELECT id FROM offer_bundles WHERE name_en = 'Special Offers' LIMIT 1")->fetch();
        if ($existing === false) {
            $pdo->exec("INSERT INTO offer_bundles (name_ar, name_en, description_ar, description_en, discount_type, discount_value, active, sort_order)
                        VALUES ('العروض الخاصة', 'Special Offers', 'أفضل العروض والخصومات', 'Best deals and discounts', 'none', 0, 1, 0)");
            $bundleId = (int)$pdo->lastInsertId();
            $ins2 = $pdo->prepare("INSERT IGNORE INTO offer_bundle_products (bundle_id, product_id) VALUES (?, ?)");
            foreach ($offerProds as $pid) {
                $ins2->execute([$bundleId, (int)$pid]);
            }
            $log[] = "✅ نقل " . count($offerProds) . " منتج من is_offer إلى bundle 'العروض الخاصة' (id=$bundleId)";
        } else {
            $log[] = "ℹ️ bundle 'Special Offers' موجود بالفعل — تم تخطي النقل";
        }
    } else {
        $log[] = "ℹ️ لا توجد منتجات is_offer=1 لنقلها";
    }
} catch (Throwable $e) {
    $errors[] = "⚠️ نقل العروض القديمة — " . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Migration — Offer Bundles</title>
<style>
body{font-family:'Tajawal',sans-serif;max-width:700px;margin:3rem auto;padding:1rem;direction:rtl;}
h1{color:#111;font-size:1.5rem;}
.ok{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:0.5rem 1rem;border-radius:8px;margin:0.3rem 0;}
.err{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:0.5rem 1rem;border-radius:8px;margin:0.3rem 0;}
a{display:inline-block;margin-top:2rem;background:#111;color:#fff;padding:0.7rem 2rem;border-radius:50px;text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<h1>🚀 Migration: Offer Bundles + Multi-Category</h1>
<?php foreach ($log as $l): ?><div class="ok"><?= htmlspecialchars($l) ?></div><?php endforeach; ?>
<?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<?php if (empty($errors)): ?>
<p style="margin-top:1.5rem;color:#155724;font-weight:700;">✅ تمت هجرة البيانات بنجاح!</p>
<?php endif; ?>
<a href="../admin/">العودة للوحة التحكم</a>
</body>
</html>
