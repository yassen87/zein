<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(t('admin_err_bad_request'));
}

admin_verify_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

$pdo = medal_pdo();
if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('DELETE FROM promo_codes WHERE id = ?');
    $st->execute([$id]);
}

header('Location: ' . admin_url('promo_codes.php'));
exit;
