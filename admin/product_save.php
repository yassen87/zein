<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

function set_flash_error(string $msg): void {
    $_SESSION['product_flash_error'] = $msg;
    $_SESSION['product_flash_data'] = $_POST;
}
function get_flash_error(): string {
    $msg = $_SESSION['product_flash_error'] ?? '';
    unset($_SESSION['product_flash_error']);
    return $msg;
}
function get_flash_data(): array {
    $data = $_SESSION['product_flash_data'] ?? [];
    unset($_SESSION['product_flash_data']);
    return $data;
}
function redirect_back_with_error(string $msg): void {
    set_flash_error($msg);
    $id = (int)($_POST['id'] ?? 0);
    $url = admin_url('product_edit.php' . ($id > 0 ? '?id=' . $id : ''));
    header('Location: ' . $url);
    exit;
}

$pdo = medal_pdo();
if ($pdo === null) {
    exit(t('admin_err_db_not_configured'));
}

$id = (int) ($_POST['id'] ?? 0);
$brandId = $_POST['brand_id'] !== '' ? (int) $_POST['brand_id'] : null;
$nameEn = trim((string) ($_POST['name_en'] ?? ''));
$nameAr = trim((string) ($_POST['name_ar'] ?? ''));
$slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
if ($slug === '' && $nameEn !== '') {
    $slug = strtolower($nameEn);
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    $slug = trim($slug, '-');
}
if ($slug === '') {
    $slug = 'product-' . time();
}

// Handle multi-category: categories[] array
$categorySlugs = array_map('strval', (array)($_POST['categories'] ?? []));
$categorySlugs = array_values(array_unique(array_filter($categorySlugs, fn($s) => $s !== '')));
// Sanitize each slug
$categorySlugs = array_map(function(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9\-]+/', '-', $s);
    return trim((string)preg_replace('/-+/', '-', $s), '-');
}, $categorySlugs);
$categorySlugs = array_values(array_filter($categorySlugs));
if (empty($categorySlugs)) {
    $categorySlugs = ['unisex'];
}
// Primary category (first selected) kept in products.category for backward compatibility
$category = $categorySlugs[0];

$flatCatSlugs = ['offers', 'brands'];
$isFlatPrice = count(array_intersect($categorySlugs, $flatCatSlugs)) > 0;

// Server-side validation: don't allow mixing flat and non-flat categories
$hasFlat = count(array_intersect($categorySlugs, $flatCatSlugs)) > 0;
$hasNonFlat = count(array_diff($categorySlugs, $flatCatSlugs)) > 0;
if ($hasFlat && $hasNonFlat) {
    redirect_back_with_error('لا يمكن دمج أقسام العروض/الماركات مع الأقسام العادية. اختر نوعاً واحداً فقط.');
}

$isBrandProduct = in_array('brands', $categorySlugs, true) ? 1 : (isset($_POST['is_brand_product']) ? (int) $_POST['is_brand_product'] : ($brandId !== null ? 1 : 0));

$season = $_POST['season'] ?? 'both';
if (!in_array($season, ['winter', 'summer', 'both'], true)) {
    $season = 'both';
}

$active = isset($_POST['active']) ? 1 : 0;
$isBestseller = isset($_POST['is_bestseller']) ? 1 : 0;
$isOffer = in_array('offers', $categorySlugs, true) ? 1 : (isset($_POST['is_offer']) ? 1 : 0);
$sortOrder = (int) ($_POST['sort_order'] ?? 0);
$notesEn = trim((string) ($_POST['notes_en'] ?? ''));
$notesAr = trim((string) ($_POST['notes_ar'] ?? ''));
$descEn = trim((string) ($_POST['description_en'] ?? ''));
$descAr = trim((string) ($_POST['description_ar'] ?? ''));
$primaryKey = trim((string) ($_POST['primary_image_key'] ?? 'default'));
if ($primaryKey === '') {
    $primaryKey = 'default';
}
$fileSharingUrl = trim((string) ($_POST['file_sharing_url'] ?? ''));
if ($fileSharingUrl !== '' && !filter_var($fileSharingUrl, FILTER_VALIDATE_URL)) {
    redirect_back_with_error('رابط مشاركة الملف غير صالح');
}

$variantRows = [];
foreach ($_POST['variants'] ?? [] as $row) {
    if (!is_array($row)) {
        continue;
    }
    $le = trim((string) ($row['label_en'] ?? ''));
    if ($le === '') {
        continue;
    }
    $la = trim((string) ($row['label_ar'] ?? ''));
    if ($la === '') {
        $la = $le;
    }
    $price = trim((string) ($row['price'] ?? ''));
    $p = $price === '' ? null : filter_var($price, FILTER_VALIDATE_FLOAT);
    if ($p === false || $p === null || $p < 0) {
        redirect_back_with_error('يرجى إدخال سعر صحيح لكل حجم');
    }
    $co = trim((string) ($row['compare_at_price'] ?? ''));
    $compare = $co === '' ? null : filter_var($co, FILTER_VALIDATE_FLOAT);
    if ($compare !== null && $compare === false) {
        $compare = null;
    }
    $variantRows[] = [
        'label_en' => $le,
        'label_ar' => $la,
        'price' => round((float) $p, 2),
        'compare_at_price' => $compare !== null ? round((float) $compare, 2) : null,
        'stock' => (int) ($row['stock'] ?? 0),
        'sort_order' => (int) ($row['sort_order'] ?? 0),
    ];
}

if ($variantRows === []) {
    if ($isFlatPrice) {
        $flatPrice = filter_var(trim((string)($_POST['price'] ?? '')), FILTER_VALIDATE_FLOAT);
        if ($flatPrice === false || $flatPrice < 0) {
            redirect_back_with_error('السعر مطلوب لمنتجات العروض والماركات');
        }
        $flatCompare = filter_var(trim((string)($_POST['compare_at_price'] ?? '')), FILTER_VALIDATE_FLOAT);
        $flatStock = (int)($_POST['stock'] ?? 50);
        $variantRows[] = [
            'label_en'         => 'Original',
            'label_ar'         => 'الأصلي',
            'price'            => round((float)$flatPrice, 2),
            'compare_at_price' => ($flatCompare !== false && $flatCompare > $flatPrice) ? round((float)$flatCompare, 2) : null,
            'stock'            => $flatStock,
            'sort_order'       => 0,
        ];
    } else {
        redirect_back_with_error('يرجى إضافة حجم واحد على الأقل مع السعر');
    }
}

if ($nameEn === '' || $nameAr === '' || $descEn === '' || $descAr === '') {
    redirect_back_with_error('الاسم والوصف (بالعربي والإنجليزي) مطلوبان');
}

$galleryKeys = [];
$gt = trim((string) ($_POST['gallery_text'] ?? ''));
if ($gt !== '') {
    foreach (preg_split('/\r\n|\r|\n/', $gt) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $galleryKeys[] = $line;
        }
    }
} else {
    foreach ($_POST['gallery_keys'] ?? [] as $gk) {
        $gk = trim((string) $gk);
        if ($gk !== '') {
            $galleryKeys[] = $gk;
        }
    }
}
if ($galleryKeys === []) {
    $galleryKeys[] = $primaryKey;
}

// Ensure column exists outside transaction (DDL causes implicit commit)
if (!isset($_SESSION['_migrated_products_brand'])) {
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_brand_product TINYINT(1) DEFAULT 0");
    } catch (Throwable $e) {}
    $_SESSION['_migrated_products_brand'] = true;
}

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $chk = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $chk->execute([$id]);
        if ($chk->fetch() === false) {
            throw new RuntimeException(t('admin_err_product_not_found'));
        }
        
        // If slug collision, append random suffix until unique
        while (true) {
            $dupe = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
            $dupe->execute([$slug, $id]);
            if ($dupe->fetch() === false) break;
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $u = $pdo->prepare(
            'UPDATE products SET slug=?, brand_id=?, is_brand_product=?, category=?, season=?, is_bestseller=?, is_offer=?, active=?,
             name_en=?, name_ar=?, notes_en=?, notes_ar=?, description_en=?, description_ar=?,
             primary_image_key=?, sort_order=?, file_sharing_url=? WHERE id=?'
        );
        $u->execute([
            $slug, $brandId, $isBrandProduct, $category, $season, $isBestseller, $isOffer, $active,
            $nameEn, $nameAr, $notesEn, $notesAr, $descEn, $descAr,
            $primaryKey, $sortOrder, $fileSharingUrl, $id,
        ]);
        $newId = $id;
    } else {
        // If slug collision, append random suffix until unique
        while (true) {
            $dupe = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
            $dupe->execute([$slug]);
            if ($dupe->fetch() === false) break;
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $ins = $pdo->prepare(
            'INSERT INTO products (slug, brand_id, is_brand_product, category, season, is_bestseller, is_offer, active,
             name_en, name_ar, notes_en, notes_ar, description_en, description_ar, primary_image_key, sort_order, file_sharing_url)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $slug, $brandId, $isBrandProduct, $category, $season, $isBestseller, $isOffer, $active,
            $nameEn, $nameAr, $notesEn, $notesAr, $descEn, $descAr,
            $primaryKey, $sortOrder, $fileSharingUrl,
        ]);
        $newId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$newId]);
    try {
        $vins = $pdo->prepare(
            'INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, sort_order, stock) VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($variantRows as $vr) {
            $vins->execute([
                $newId,
                $vr['label_en'],
                $vr['label_ar'],
                $vr['price'],
                $vr['compare_at_price'],
                $vr['sort_order'],
                $vr['stock'],
            ]);
        }
    } catch (Throwable) {
        // stock column may not exist yet — save without it (run migrate.php to fix)
        $vins = $pdo->prepare(
            'INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, sort_order) VALUES (?,?,?,?,?,?)'
        );
        foreach ($variantRows as $vr) {
            $vins->execute([
                $newId,
                $vr['label_en'],
                $vr['label_ar'],
                $vr['price'],
                $vr['compare_at_price'],
                $vr['sort_order'],
            ]);
        }
    }

    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$newId]);
    $iins = $pdo->prepare('INSERT INTO product_images (product_id, image_key, sort_order) VALUES (?,?,?)');
    foreach (array_values($galleryKeys) as $i => $gk) {
        $iins->execute([$newId, $gk, $i]);
    }

    // Sync product_categories pivot table
    try {
        $pdo->prepare('DELETE FROM product_categories WHERE product_id = ?')->execute([$newId]);
        $cins = $pdo->prepare('INSERT IGNORE INTO product_categories (product_id, category_slug) VALUES (?, ?)');
        foreach ($categorySlugs as $slug) {
            $cins->execute([$newId, $slug]);
        }
    } catch (Throwable) {
        // product_categories table may not exist yet — run migrate_bundles.php to create it
    }

    $pdo->commit();

    // WhatsApp Broadcast if enabled
    if (!empty($_POST['broadcast_to_whatsapp'])) {
        require_once __DIR__ . '/../includes/whatsapp_helper.php';
        $firstPrice = 0.0;
        if (!empty($variantRows)) {
            $firstPrice = (float)($variantRows[0]['price'] ?? 0);
        }
        $broadcastData = [
            'productId' => $newId,
            'nameAr' => $nameAr,
            'nameEn' => $nameEn,
            'description' => $descAr ?: $notesAr,
            'price' => $firstPrice,
            'slug' => $slug,
            'productUrl' => storefront_url('product.php?id=' . (int)$newId)
        ];
        broadcast_whatsapp_new_product($broadcastData);
    }

    // Notify Admins about the new product
    if ($id === 0) {
        add_admin_notification(
            'new_product',
            'منتج جديد: ' . $nameAr,
            'New Product: ' . $nameEn,
            'تمت إضافة منتج جديد إلى المتجر: ' . $nameAr,
            'A new product has been added to the store: ' . $nameEn,
            'product_edit.php?id=' . $newId
        );
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect_back_with_error('حدث خطأ أثناء الحفظ: ' . $e->getMessage());
}

$redirectTarget = 'products.php';
if (in_array('offers', $categorySlugs, true)) {
    $redirectTarget = 'offers.php';
} elseif (in_array('brands', $categorySlugs, true)) {
    $redirectTarget = 'brand_products.php';
}

header('Location: ' . admin_url($redirectTarget));
exit;
