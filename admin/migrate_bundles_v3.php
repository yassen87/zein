<?php
/**
 * Migration v3: Support multi-piece single-product offers in offer_bundles
 * Run this once via browser: /admin/migrate_bundles_v3.php?key=zain_migrate_2025
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';

session_start();
if (($_GET['key'] ?? '') !== 'zain_migrate_2025') {
    http_response_code(403);
    exit('Unauthorized.');
}

$pdo = medal_pdo();
if (!$pdo) {
    exit('Database not connected.');
}

$log = [];
$errors = [];

try {
    // Add columns to offer_bundles
    $pdo->exec("ALTER TABLE offer_bundles ADD COLUMN IF NOT EXISTS product_id INT UNSIGNED DEFAULT NULL");
    $log[] = "✅ Added product_id column to offer_bundles";
    
    $pdo->exec("ALTER TABLE offer_bundles ADD COLUMN IF NOT EXISTS variant_id INT UNSIGNED DEFAULT NULL");
    $log[] = "✅ Added variant_id column to offer_bundles";
    
    $pdo->exec("ALTER TABLE offer_bundles ADD COLUMN IF NOT EXISTS quantity INT DEFAULT 2");
    $log[] = "✅ Added quantity column to offer_bundles";
    
    $pdo->exec("ALTER TABLE offer_bundles ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0.00");
    $log[] = "✅ Added price column to offer_bundles";
    
    // Migrate existing bundles if any
    $bundles = $pdo->query("SELECT id FROM offer_bundles")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($bundles as $bid) {
        // Fetch first associated product
        $st = $pdo->prepare("SELECT product_id, offer_price FROM offer_bundle_products WHERE bundle_id = ? LIMIT 1");
        $st->execute([$bid]);
        $bp = $st->fetch();
        if ($bp) {
            $pid = (int)$bp['product_id'];
            $oprice = (float)$bp['offer_price'];
            
            // Get default variant
            $st2 = $pdo->prepare("SELECT id, price FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
            $st2->execute([$pid]);
            $v = $st2->fetch();
            $vid = $v ? (int)$v['id'] : null;
            if ($oprice <= 0 && $v) {
                $oprice = (float)$v['price'];
            }
            
            $upd = $pdo->prepare("UPDATE offer_bundles SET product_id = ?, variant_id = ?, quantity = 2, price = ? WHERE id = ?");
            $upd->execute([$pid, $vid, $oprice, $bid]);
        }
    }
    $log[] = "✅ Migrated existing bundle associations to new schema columns";

} catch (Throwable $e) {
    $errors[] = "⚠️ Error: " . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Migration v3 — Offer Bundles</title>
<style>
body{font-family:'Tajawal',sans-serif;max-width:700px;margin:3rem auto;padding:1rem;direction:rtl;}
h1{color:#111;font-size:1.5rem;}
.ok{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:0.5rem 1rem;border-radius:8px;margin:0.3rem 0;}
.err{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:0.5rem 1rem;border-radius:8px;margin:0.3rem 0;}
a{display:inline-block;margin-top:2rem;background:#111;color:#fff;padding:0.7rem 2rem;border-radius:50px;text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<h1>🚀 Migration v3: Multi-Piece Offers</h1>
<?php foreach ($log as $l): ?><div class="ok"><?= htmlspecialchars($l) ?></div><?php endforeach; ?>
<?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<?php if (empty($errors)): ?>
<p style="margin-top:1.5rem;color:#155724;font-weight:700;">✅ Migration completed successfully!</p>
<?php endif; ?>
<a href="../admin/offers.php">العودة لإدارة العروض</a>
</body>
</html>
