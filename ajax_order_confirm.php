<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = medal_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? 'confirm')); // 'confirm' | 'set_payment' | 'cancel'
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'cod'));

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    if ($action === 'confirm') {
        $st = $pdo->prepare("UPDATE orders SET is_confirmed = 1, confirmed_at = COALESCE(confirmed_at, NOW()) WHERE id = ?");
        $st->execute([$orderId]);
        echo json_encode(['success' => true, 'message' => 'تم تأكيد الطلب بنجاح']);
        exit;
    }

    if ($action === 'set_payment') {
        if (!in_array($paymentMethod, ['instapay', 'vodafone_cash', 'cod', 'bank_transfer'], true)) {
            $paymentMethod = 'cod';
        }
        $st = $pdo->prepare("UPDATE orders SET payment_method = ?, is_confirmed = 1, confirmed_at = COALESCE(confirmed_at, NOW()) WHERE id = ?");
        $st->execute([$paymentMethod, $orderId]);
        echo json_encode(['success' => true, 'message' => 'تم حفظ طريقة الدفع وتأكيد الطلب']);
        exit;
    }

    if ($action === 'cancel') {
        $st = $pdo->prepare("UPDATE orders SET status = 'cancelled', bot_step = 'cancelled' WHERE id = ?");
        $st->execute([$orderId]);
        echo json_encode(['success' => true, 'message' => 'تم إلغاء الطلب بنجاح']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (\Throwable $e) {
    error_log('Error in ajax_order_confirm.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
