<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = medal_pdo();
if ($pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB not configured']);
    exit;
}

admin_verify_csrf();

$orderId = (int) ($_POST['order_id'] ?? 0);
$field   = (string) ($_POST['field'] ?? '');
$value   = (string) ($_POST['value'] ?? '');

if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    if ($field === 'status') {
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($value, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }
        $prevStatusSt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
        $prevStatusSt->execute([$orderId]);
        $prevStatus = $prevStatusSt->fetchColumn();

        $st = $pdo->prepare("UPDATE orders SET status = ?, delivered_at = CASE WHEN ? = 'delivered' THEN COALESCE(delivered_at, NOW()) ELSE NULL END WHERE id = ?");
        $st->execute([$value, $value, $orderId]);

        // Send WhatsApp notification if status changed
        if ($prevStatus !== $value) {
            require_once __DIR__ . '/../includes/whatsapp_helper.php';
            send_whatsapp_order_status_notification($orderId, $value);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown field']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
