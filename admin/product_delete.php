<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1 || ($_POST['delete_product'] ?? '') !== '1') {
    http_response_code(400);
    exit(t('admin_err_bad_request'));
}

$pdo = medal_pdo();
if ($pdo === null) {
    exit(t('admin_err_db_not_configured'));
}

$uploadDir = realpath(__DIR__ . '/../assets/uploads');

function delete_product_upload_variants(?string $imageKey, $uploadDir): void
{
    $imageKey = trim((string) $imageKey);
    if ($uploadDir === false || $uploadDir === null || $imageKey === '' || $imageKey === 'default' || preg_match('~^https?://~i', $imageKey)) {
        return;
    }

    $filename = basename($imageKey);
    if ($filename === '' || $filename !== $imageKey) {
        return;
    }

    $info = pathinfo($filename);
    $base = $info['filename'] ?? '';
    $ext = strtolower($info['extension'] ?? '');
    $candidates = [$filename];
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $variantExt) {
        if ($base !== '' && $variantExt !== $ext) {
            $candidates[] = $base . '.' . $variantExt;
        }
    }

    foreach (array_unique($candidates) as $candidate) {
        $path = $uploadDir . DIRECTORY_SEPARATOR . $candidate;
        $realPath = realpath($path);
        if ($realPath !== false && str_starts_with($realPath, $uploadDir . DIRECTORY_SEPARATOR) && is_file($realPath)) {
            unlink($realPath);
        }
    }
}

$images = [];
$st = $pdo->prepare('SELECT primary_image_key FROM products WHERE id = ?');
$st->execute([$id]);
$primaryImage = $st->fetchColumn();
if ($primaryImage !== false) {
    $images[] = (string) $primaryImage;
}

$st = $pdo->prepare('SELECT image_key FROM product_images WHERE product_id = ?');
$st->execute([$id]);
foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $imageKey) {
    $images[] = (string) $imageKey;
}

foreach (array_unique($images) as $imageKey) {
    delete_product_upload_variants($imageKey, $uploadDir);
}

$pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
try { $pdo->prepare('DELETE FROM product_categories WHERE product_id = ?')->execute([$id]); } catch (Throwable) {}
try { $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$id]); } catch (Throwable) {}
$pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

$redirectTo = trim((string)($_POST['redirect_to'] ?? ''));
if ($redirectTo === 'offers') {
    header('Location: ' . admin_url('offers.php?deleted=1'));
} elseif ($redirectTo === 'brand_products') {
    header('Location: ' . admin_url('brand_products.php?deleted=1'));
} else {
    header('Location: ' . admin_url('products.php?deleted=1'));
}
exit;
