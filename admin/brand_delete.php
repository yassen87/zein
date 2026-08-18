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

$pdo = medal_pdo();
if ($pdo !== null && $id > 0) {
    // We have ON DELETE SET NULL on the foreign key, so this is safe
    $st = $pdo->prepare('DELETE FROM brands WHERE id = ?');
    $st->execute([$id]);
}

header('Location: ' . admin_url('brands.php'));
exit;
