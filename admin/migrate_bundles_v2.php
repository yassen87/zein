<?php
/**
 * Migration v2: Add offer_price to offer_bundle_products
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    if (($_GET['key'] ?? '') !== 'zain_migrate_2025') {
        http_response_code(403);
        exit('Unauthorized.');
    }
}

$pdo = medal_pdo();
if (!$pdo) exit('Database not connected.');

$log = [];
$errors = [];

// Add offer_price column to offer_bundle_products
try {
    $pdo->exec("ALTER TABLE offer_bundle_products ADD COLUMN IF NOT EXISTS offer_price DECIMAL(10,2) NULL DEFAULT NULL");
    $log[] = "✅ تم إضافة عمود offer_price إلى offer_bundle_products";
} catch (Throwable $e) {
    // Try without IF NOT EXISTS (older MySQL)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM offer_bundle_products LIKE 'offer_price'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE offer_bundle_products ADD COLUMN offer_price DECIMAL(10,2) NULL DEFAULT NULL");
            $log[] = "✅ تم إضافة عمود offer_price إلى offer_bundle_products";
        } else {
            $log[] = "ℹ️ عمود offer_price موجود بالفعل";
        }
    } catch (Throwable $e2) {
        $errors[] = "⚠️ " . $e2->getMessage();
    }
}

// Add sort_order to offer_bundle_products for ordering products within a bundle
try {
    $pdo->exec("ALTER TABLE offer_bundle_products ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0");
    $log[] = "✅ تم إضافة عمود sort_order إلى offer_bundle_products";
} catch (Throwable $e) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM offer_bundle_products LIKE 'sort_order'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE offer_bundle_products ADD COLUMN sort_order INT DEFAULT 0");
            $log[] = "✅ تم إضافة عمود sort_order";
        } else {
            $log[] = "ℹ️ عمود sort_order موجود بالفعل";
        }
    } catch (Throwable) {}
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>Migration v2</title>
<style>
body{font-family:'Tajawal',sans-serif;max-width:700px;margin:3rem auto;padding:1rem;direction:rtl;}
h1{color:#111;}
.ok{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:.5rem 1rem;border-radius:8px;margin:.3rem 0;}
.err{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:.5rem 1rem;border-radius:8px;margin:.3rem 0;}
a{display:inline-block;margin-top:2rem;background:#111;color:#fff;padding:.7rem 2rem;border-radius:50px;text-decoration:none;font-weight:700;}
</style></head>
<body>
<h1>🚀 Migration v2: Offer Price per Product</h1>
<?php foreach ($log as $l): ?><div class="ok"><?= htmlspecialchars($l) ?></div><?php endforeach; ?>
<?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<?php if (empty($errors)): ?><p style="margin-top:1.5rem;color:#155724;font-weight:700;">✅ تم بنجاح!</p><?php endif; ?>
<a href="../admin/offers.php">العودة لإدارة العروض</a>
</body></html>
