<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('brands.php'));
    exit;
}

if (!admin_csrf_verify((string) ($_POST['csrf'] ?? ''))) {
    die('CSRF token mismatch');
}

$id = (int) ($_POST['id'] ?? 0);
$name_en = trim((string) ($_POST['name_en'] ?? ''));
$name_ar = trim((string) ($_POST['name_ar'] ?? ''));
$logo = trim((string) ($_POST['logo'] ?? ''));
$description_en = trim((string) ($_POST['description_en'] ?? ''));
$description_ar = trim((string) ($_POST['description_ar'] ?? ''));
$country_en = trim((string) ($_POST['country_en'] ?? ''));
$country_ar = trim((string) ($_POST['country_ar'] ?? ''));
$is_popular = (int) ($_POST['is_popular'] ?? 0);
$sort_order = (int) ($_POST['sort_order'] ?? 0);

$pdo = medal_pdo();
if ($pdo === null) {
    die('Database connection failed');
}

if ($id > 0) {
    $st = $pdo->prepare('UPDATE brands SET name_en = ?, name_ar = ?, logo = ?, description_en = ?, description_ar = ?, country_en = ?, country_ar = ?, is_popular = ?, sort_order = ? WHERE id = ?');
    $st->execute([$name_en, $name_ar, $logo, $description_en, $description_ar, $country_en, $country_ar, $is_popular, $sort_order, $id]);
} else {
    $st = $pdo->prepare('INSERT INTO brands (name_en, name_ar, logo, description_en, description_ar, country_en, country_ar, is_popular, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $st->execute([$name_en, $name_ar, $logo, $description_en, $description_ar, $country_en, $country_ar, $is_popular, $sort_order]);
}

header('Location: ' . admin_url('brands.php'));
exit;
