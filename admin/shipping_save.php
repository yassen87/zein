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

$deliveryTime = trim((string) ($_POST['delivery_time'] ?? '1-3 أيام عمل'));
$active = isset($_POST['active']) ? 1 : 1;

$pdo = medal_pdo();
if ($pdo !== null) {
    medal_ensure_column($pdo, 'shipping_cities', 'active', 'TINYINT(1) NOT NULL DEFAULT 1');
    medal_ensure_column($pdo, 'shipping_cities', 'delivery_time', 'VARCHAR(100) NULL DEFAULT "1-3 أيام عمل"');

    if ($id > 0) {
        $st = $pdo->prepare('UPDATE shipping_cities SET name_en=?, name_ar=?, shipping_cost=?, sort_order=?, delivery_time=?, active=? WHERE id=?');
        $st->execute([$nameEn, $nameAr, $cost, $sortOrder, $deliveryTime, $active, $id]);
    } else {
        $st = $pdo->prepare('INSERT INTO shipping_cities (name_en, name_ar, shipping_cost, sort_order, delivery_time, active) VALUES (?,?,?,?,?,?)');
        $st->execute([$nameEn, $nameAr, $cost, $sortOrder, $deliveryTime, $active]);
    }
}

header('Location: ' . admin_url('shipping.php'));
exit;
