<?php
/**
 * Migration v4: Convert offer bundles from offer_bundles table into standalone products in products table where is_offer = 1
 * Run this from command line: c:\xampp\php\php.exe database\migrate_to_v4.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$pdo = medal_pdo();
if (!$pdo) {
    die("Database connection failed.\n");
}

try {
    $pdo->beginTransaction();

    // Check if there are any old bundles to migrate
    $bundles = [];
    try {
        $bundles = $pdo->query("SELECT * FROM offer_bundles")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        echo "No offer_bundles table or error fetching bundles: " . $e->getMessage() . "\n";
    }

    echo "Found " . count($bundles) . " bundles to migrate.\n";

    foreach ($bundles as $b) {
        $nameAr = $b['name_ar'];
        $nameEn = $b['name_en'];
        $descAr = $b['description_ar'] ?? '';
        $descEn = $b['description_en'] ?? '';
        $imageKey = $b['image_key'] !== '' ? $b['image_key'] : 'default';
        $price = (float)$b['price'];
        $active = (int)$b['active'];
        $sortOrder = (int)$b['sort_order'];

        // Get primary product details to fallback image if needed
        $productId = $b['product_id'] ? (int)$b['product_id'] : null;
        if ($productId && $imageKey === 'default') {
            $st = $pdo->prepare("SELECT primary_image_key FROM products WHERE id = ?");
            $st->execute([$productId]);
            $prodImg = $st->fetchColumn();
            if ($prodImg) {
                $imageKey = $prodImg;
            }
        }

        // Generate a unique slug
        $slug = strtolower($nameEn);
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'offer-' . time() . '-' . rand(100, 999);
        }
        
        // Ensure slug is unique in products
        $originalSlug = $slug;
        $count = 1;
        while (true) {
            $st = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
            $st->execute([$slug]);
            if ($st->fetch() === false) {
                break;
            }
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        // Insert into products table
        $ins = $pdo->prepare(
            "INSERT INTO products (slug, is_offer, is_brand_product, category, name_en, name_ar, description_en, description_ar, primary_image_key, sort_order, active)
             VALUES (?, 1, 0, 'unisex-offers', ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([
            $slug,
            $nameEn,
            $nameAr,
            $descEn,
            $descAr,
            $imageKey,
            $sortOrder,
            $active
        ]);

        $newProductId = (int)$pdo->lastInsertId();

        // Calculate compare at price (price * quantity)
        $quantity = (int)($b['quantity'] ?? 2);
        $comparePrice = $price;
        if ($productId && isset($b['variant_id']) && $b['variant_id']) {
            $st = $pdo->prepare("SELECT price FROM product_variants WHERE id = ?");
            $st->execute([$b['variant_id']]);
            $origPrice = (float)$st->fetchColumn();
            if ($origPrice > 0) {
                $comparePrice = $origPrice * $quantity;
            }
        }

        // Insert variant
        $vins = $pdo->prepare(
            "INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, stock, sort_order)
             VALUES (?, 'Original', 'الأصلي', ?, ?, 50, 0)"
        );
        $vins->execute([
            $newProductId,
            $price,
            $comparePrice > $price ? $comparePrice : null
        ]);

        // Insert product category association
        try {
            $cins = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_slug) VALUES (?, 'unisex-offers')");
            $cins->execute([$newProductId]);
        } catch (Throwable $e) {}

        echo "Migrated bundle #{$b['id']} as product #{$newProductId} with slug: {$slug}\n";
    }

    $pdo->commit();
    echo "Migration to v4 completed successfully.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Migration failed: " . $e->getMessage() . "\n");
}
