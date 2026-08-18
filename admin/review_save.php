<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
admin_verify_csrf();

$pdo = medal_pdo();
if ($pdo === null) {
    exit('DB NOT CONFIGURED');
}

$id = (int) ($_POST['id'] ?? 0);
$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$rating = (int) ($_POST['rating'] ?? 5);
$reviewText = trim((string) ($_POST['review_text'] ?? ''));

if ($id <= 0 || $customerName === '') {
    header('Location: ' . admin_url('reviews.php?err=required_fields_missing'));
    exit;
}

if ($rating < 1) $rating = 1;
if ($rating > 5) $rating = 5;

try {
    $st = $pdo->prepare('UPDATE product_reviews SET customer_name = ?, rating = ?, review_text = ? WHERE id = ?');
    $st->execute([$customerName, $rating, $reviewText !== '' ? $reviewText : null, $id]);
} catch (\Exception $e) {
    header('Location: ' . admin_url('reviews.php?err=' . urlencode($e->getMessage())));
    exit;
}

header('Location: ' . admin_url('reviews.php?saved=1'));
exit;
