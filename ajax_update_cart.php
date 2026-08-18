<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
    echo json_encode(['error' => 'Session expired. Please refresh the page.']);
    exit;
}

$lineKey = (string) ($_POST['line_key'] ?? '');
$action = (string) ($_POST['action'] ?? '');
$qty = (int) ($_POST['qty'] ?? 1);

if ($lineKey === '' || !isset($_SESSION['cart'][$lineKey])) {
    echo json_encode(['error' => 'Invalid line key']);
    exit;
}

if ($action === 'remove') {
    remove_cart_line($lineKey);
} elseif ($action === 'update_qty') {
    if ($qty > 0) {
        $_SESSION['cart'][$lineKey] = $qty;
    } else {
        remove_cart_line($lineKey);
    }
}

$cartCount = cart_count();
$newQty = $_SESSION['cart'][$lineKey] ?? 0;

$lineTotal = 0.0;
if (strpos((string)$lineKey, 'bundle-') === 0) {
    $bid = (int)str_replace('bundle-', '', (string)$lineKey);
    $p = get_cart_line_bundle($bid);
    if ($p) {
        $lineTotal = $p['price'] * $newQty;
    }
} else {
    $parsed = cart_parse_line_key($lineKey);
    $p = get_cart_line_product($parsed['product_id'], $parsed['variant_id']);
    if ($p) {
        $lineTotal = $p['price'] * $newQty;
    }
}

$total = 0.0;
foreach ($_SESSION['cart'] ?? [] as $lk => $q) {
    if (strpos((string)$lk, 'bundle-') === 0) {
        $bid = (int)str_replace('bundle-', '', (string)$lk);
        $pr = get_cart_line_bundle($bid);
        if ($pr) {
            $total += $pr['price'] * $q;
        }
    } else {
        $psd = cart_parse_line_key((string) $lk);
        $pr = get_cart_line_product($psd['product_id'], $psd['variant_id']);
        if ($pr) {
            $total += $pr['price'] * $q;
        }
    }
}

echo json_encode([
    'success' => true,
    'new_qty' => $newQty,
    'line_total' => format_price($lineTotal),
    'cart_count' => $cartCount,
    'subtotal' => $total,
    'subtotal_formatted' => format_price($total)
]);
