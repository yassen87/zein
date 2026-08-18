<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

// Route cart actions to checkout page since it's a one-page checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        header('Location: ' . url('products.php'));
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pid = (int) ($_POST['product_id'] ?? $_POST['id'] ?? 0);
        $bid = (int) ($_POST['bundle_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? $_POST['quantity'] ?? 1);
        $variantRaw = $_POST['variant_id'] ?? '';
        $variantId = $variantRaw === '' ? null : (int) $variantRaw;
        if ($variantId === 0) {
            $variantId = null;
        }

        $success = false;
        $womenMessage = null;
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);

        if ($bid > 0) {
            $lk = 'bundle-' . $bid;
            if (!isset($_SESSION['cart'][$lk])) {
                $_SESSION['cart'][$lk] = 0;
            }
            $_SESSION['cart'][$lk] += $qty;
            $success = true;
        } elseif ($pid > 0 && ($product = get_product_by_id($pid)) !== null) {
            add_to_cart($pid, $qty, $variantId);
            $success = true;
            $categories = $product['categories'] ?? [$product['category']];
            if (in_array('women', $categories, true)) {
                $womenMessage = get_setting('women_category_cart_message') ?: t('women_category_cart_message');
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'women_message' => $womenMessage]);
            exit;
        }

        if ($success && $womenMessage !== null) {
            $_SESSION['women_category_cart_alert'] = $womenMessage;
        }

        if ($success) {
            header('Location: ' . url('checkout.php'));
        } else {
            header('Location: ' . url('products.php'));
        }
        exit;
    }
}

header('Location: ' . url('checkout.php'));
exit;
