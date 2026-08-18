<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if (!$pdo) exit('DB error');

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $pdo->prepare('DELETE FROM offer_bundles WHERE id = ?')->execute([$id]);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('خطأ: ' . $e->getMessage());
    }
}

header('Location: ' . admin_url('offers.php?deleted=1'));
exit;
