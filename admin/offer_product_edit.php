<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$url = admin_url('product_edit.php' . ($id > 0 ? '?id=' . $id : ''));
header('Location: ' . $url);
exit;