<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if ($pdo === null) exit(t('admin_err_db_not_configured'));

$id        = (int)($_POST['id'] ?? 0);
$nameEn    = trim((string)($_POST['name_en'] ?? ''));
$nameAr    = trim((string)($_POST['name_ar'] ?? ''));
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if ($nameEn === '' || $nameAr === '') {
    http_response_code(400);
    exit(t('admin_err_invalid_input'));
}

$slug = '';
if ($id > 0) {
    // Keep existing slug for stability of existing links
    $st = $pdo->prepare('SELECT slug FROM categories WHERE id = ?');
    $st->execute([$id]);
    $slug = (string)$st->fetchColumn();
}

if ($slug === '') {
    // Generate slug from English Name
    $slug = strtolower($nameEn);
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    $slug = trim((string)preg_replace('/-+/', '-', $slug), '-');
    if ($slug === '') {
        $slug = 'cat-' . bin2hex(random_bytes(3));
    }
}

// Handle category image upload
$uploadDir   = dirname(__DIR__) . '/assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
$imageValue  = trim((string)($_POST['cat_image_existing'] ?? ''));

if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['cat_image']['size'] > 1 * 1024 * 1024) {
        http_response_code(400);
        exit('حجم الصورة كبير جداً. الحد الأقصى 1 ميجابايت / 1MB.');
    }
    $tmp = $_FILES['cat_image']['tmp_name'];
    $ext = strtolower(pathinfo(basename($_FILES['cat_image']['name']), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
        http_response_code(400);
        exit('صيغة الصورة غير مدعومة.');
    }
    $filename   = 'cat_' . $slug . '_' . time() . '.' . $ext;
    move_uploaded_file($tmp, $uploadDir . $filename);
    $imageValue = $filename;
}

if ($id > 0) {
    while (true) {
        $dupe = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $dupe->execute([$slug, $id]);
        if ($dupe->fetch() === false) break;
        $slug .= '-' . bin2hex(random_bytes(2));
    }
    try {
        $pdo->prepare('UPDATE categories SET slug=?, name_en=?, name_ar=?, sort_order=?, image=? WHERE id=?')
            ->execute([$slug, $nameEn, $nameAr, $sortOrder, $imageValue, $id]);
    } catch (Throwable) {
        // image column may not exist yet
        $pdo->prepare('UPDATE categories SET slug=?, name_en=?, name_ar=?, sort_order=? WHERE id=?')
            ->execute([$slug, $nameEn, $nameAr, $sortOrder, $id]);
    }
} else {
    while (true) {
        $dupe = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
        $dupe->execute([$slug]);
        if ($dupe->fetch() === false) break;
        $slug .= '-' . bin2hex(random_bytes(2));
    }
    try {
        $pdo->prepare('INSERT INTO categories (slug, name_en, name_ar, sort_order, image) VALUES (?,?,?,?,?)')
            ->execute([$slug, $nameEn, $nameAr, $sortOrder, $imageValue]);
    } catch (Throwable) {
        $pdo->prepare('INSERT INTO categories (slug, name_en, name_ar, sort_order) VALUES (?,?,?,?)')
            ->execute([$slug, $nameEn, $nameAr, $sortOrder]);
    }
}

header('Location: ' . admin_url('categories.php'));
exit;
