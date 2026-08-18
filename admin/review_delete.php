<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
$id = (int) ($_POST['id'] ?? 0);

if ($pdo !== null && $id > 0) {
    try {
        $st = $pdo->prepare('DELETE FROM product_reviews WHERE id = ?');
        $st->execute([$id]);
    } catch (\Exception $e) {
        header('Location: ' . admin_url('reviews.php?err=' . urlencode($e->getMessage())));
        exit;
    }
}

header('Location: ' . admin_url('reviews.php?deleted=1'));
exit;
