<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(t('admin_err_bad_request'));
}

admin_verify_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nameEn = trim((string) ($_POST['name_en'] ?? ''));
$nameAr = trim((string) ($_POST['name_ar'] ?? ''));
$cost = (float) ($_POST['shipping_cost'] ?? 0);
$sortOrder = (int) ($_POST['sort_order'] ?? 0);

if ($nameEn === '' || $nameAr === '') {
    exit(t('admin_err_invalid_input'));
}

$pdo = medal_pdo();
if ($pdo !== null) {
    if ($id > 0) {
        $st = $pdo->prepare('UPDATE shipping_cities SET name_en=?, name_ar=?, shipping_cost=?, sort_order=? WHERE id=?');
        $st->execute([$nameEn, $nameAr, $cost, $sortOrder, $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO shipping_cities (name_en, name_ar, shipping_cost, sort_order) VALUES (?,?,?,?)');
        $st->execute([$nameEn, $nameAr, $cost, $sortOrder]);
    }
}

header('Location: ' . admin_url('shipping.php'));
exit;
