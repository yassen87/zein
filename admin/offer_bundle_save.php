<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if (!$pdo) exit('DB error');

$id         = (int)($_POST['id'] ?? 0);
$nameAr     = trim((string)($_POST['name_ar'] ?? ''));
$nameEn     = trim((string)($_POST['name_en'] ?? ''));
$descAr     = trim((string)($_POST['description_ar'] ?? ''));
$descEn     = trim((string)($_POST['description_en'] ?? ''));
$discType   = $_POST['discount_type'] ?? 'none';
if (!in_array($discType, ['none','percent','fixed'], true)) $discType = 'none';
$discValue  = (float)($_POST['discount_value'] ?? 0);
$active     = isset($_POST['active']) ? 1 : 0;
$sortOrder  = (int)($_POST['sort_order'] ?? 0);
$imageKey   = trim((string)($_POST['image_key'] ?? ''));

$productId  = (int)($_POST['product_id'] ?? 0);
$variantId  = $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
$quantity   = (int)($_POST['quantity'] ?? 2);
$price      = (float)($_POST['price'] ?? 0);

if ($nameAr === '' || $nameEn === '') {
    http_response_code(400);
    exit('اسم العرض مطلوب');
}

if ($productId <= 0) {
    http_response_code(400);
    exit('يجب تحديد المنتج الخاص بالعرض');
}

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $st = $pdo->prepare(
            'UPDATE offer_bundles SET name_ar=?, name_en=?, description_ar=?, description_en=?,
             image_key=?, discount_type=?, discount_value=?, active=?, sort_order=?,
             product_id=?, variant_id=?, quantity=?, price=?
             WHERE id=?'
        );
        $st->execute([$nameAr, $nameEn, $descAr, $descEn, $imageKey, $discType, $discValue, $active, $sortOrder, $productId, $variantId, $quantity, $price, $id]);
    } else {
        $st = $pdo->prepare(
            'INSERT INTO offer_bundles (name_ar, name_en, description_ar, description_en,
             image_key, discount_type, discount_value, active, sort_order, product_id, variant_id, quantity, price)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([$nameAr, $nameEn, $descAr, $descEn, $imageKey, $discType, $discValue, $active, $sortOrder, $productId, $variantId, $quantity, $price]);
        $id = (int)$pdo->lastInsertId();
    }

    // Sync product pivot (for backwards compatibility if any queries still use it)
    $pdo->prepare('DELETE FROM offer_bundle_products WHERE bundle_id = ?')->execute([$id]);
    $ins = $pdo->prepare('INSERT IGNORE INTO offer_bundle_products (bundle_id, product_id, offer_price, sort_order) VALUES (?, ?, ?, 0)');
    $ins->execute([$id, $productId, $price]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit('خطأ: ' . $e->getMessage());
}

header('Location: ' . admin_url('offers.php?saved=1'));
exit;
