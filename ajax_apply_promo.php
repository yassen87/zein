<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
    echo json_encode(['error' => 'Session expired. Please refresh the page.']);
    exit;
}

$code = trim((string) ($_POST['code'] ?? ''));

if ($code === '') {
    echo json_encode(['error' => t('checkout_err_promo_invalid') ?? 'Invalid promo code']);
    exit;
}

$pdo = medal_pdo();
if ($pdo === null) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $st = $pdo->prepare('SELECT id, code, discount_percentage, usage_limit, used_count FROM promo_codes WHERE code = ? AND active = 1');
    $st->execute([$code]);
    $row = $st->fetch();

    if (!$row) {
        echo json_encode(['error' => t('checkout_err_promo_invalid') ?? 'Invalid promo code']);
        exit;
    }

    if ($row['usage_limit'] > 0 && $row['used_count'] >= $row['usage_limit']) {
        echo json_encode(['error' => t('checkout_err_promo_limit') ?? 'Promo code limit reached']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'discount_percentage' => (int) $row['discount_percentage']
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Error checking promo code']);
}
