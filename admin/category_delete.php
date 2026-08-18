<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit(t('admin_err_bad_request'));
}

$pdo = medal_pdo();
if ($pdo === null) {
    exit(t('admin_err_db_not_configured'));
}

$pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
header('Location: ' . admin_url('categories.php'));
exit;
