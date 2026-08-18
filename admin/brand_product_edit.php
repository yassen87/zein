<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$params = [];
if ($id > 0) $params[] = 'id=' . $id;
if ($brandId > 0) $params[] = 'brand_id=' . $brandId;
if (empty($params)) $params[] = 'cat=brands';
$url = admin_url('brand_edit.php' . (!empty($params) ? '?' . implode('&', $params) : ''));
header('Location: ' . $url);
exit;