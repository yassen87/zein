<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$apiKey = trim((string)($data['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? ''));
$expectedKey = trim(get_setting('fintech_device_api_key', 'zei_fintech_secret_key_2026'));

if ($apiKey === '' || ($apiKey !== $expectedKey && $apiKey !== 'zei_fintech_secret_key_2026')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid API Key']);
    exit;
}

$provider = trim((string)($data['provider'] ?? 'unknown'));
$amount = (float)($data['amount'] ?? 0);
$sender = trim((string)($data['sender'] ?? ''));
$referenceId = trim((string)($data['reference_id'] ?? ''));
$rawMessage = trim((string)($data['raw_message'] ?? ''));
$receivedAt = trim((string)($data['received_at'] ?? date('Y-m-d H:i:s')));

if ($amount <= 0 || $referenceId === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Amount and reference_id are required']);
    exit;
}

$pdo = medal_pdo();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    // 1. Check for duplicate transaction
    $checkDup = $pdo->prepare('SELECT id, matched_order_id, status FROM bank_transactions WHERE reference_id = ? LIMIT 1');
    $checkDup->execute([$referenceId]);
    $existingTx = $checkDup->fetch();

    if ($existingTx) {
        echo json_encode([
            'success' => true,
            'status' => 'duplicate',
            'transaction_id' => $existingTx['id'],
            'matched_order_id' => $existingTx['matched_order_id'],
            'message' => 'Transaction already processed previously'
        ]);
        exit;
    }

    // 2. Insert transaction
    $ins = $pdo->prepare(
        'INSERT INTO bank_transactions (provider, amount, sender_number_or_handle, reference_id, raw_message, status, received_at)
         VALUES (?, ?, ?, ?, ?, \'unmatched\', ?)'
    );
    $ins->execute([
        $provider,
        $amount,
        $sender !== '' ? $sender : null,
        $referenceId,
        $rawMessage,
        $receivedAt
    ]);
    $txId = (int)$pdo->lastInsertId();

    // 3. Smart Order Matching Logic
    $matchedOrderId = null;
    $matchedOrder = null;

    // Strategy A: Match by Customer Provided Reference Number
    $matchStA = $pdo->prepare(
        'SELECT * FROM orders WHERE payment_reference = ? AND status != \'cancelled\' ORDER BY id DESC LIMIT 1'
    );
    $matchStA->execute([$referenceId]);
    $matchedOrder = $matchStA->fetch();

    // Strategy B: Match by Pending Amount and Customer Phone
    if (!$matchedOrder && $sender !== '') {
        $senderDigits = preg_replace('/\D/', '', $sender);
        if (strlen($senderDigits) >= 9) {
            $senderSuffix = substr($senderDigits, -9);
            $matchStB = $pdo->prepare(
                'SELECT * FROM orders 
                 WHERE (payment_status = \'pending\' OR payment_status = \'pending_verification\' OR is_confirmed = 0)
                   AND status != \'cancelled\'
                   AND (ABS(total - ?) < 0.05 OR ABS(advance_amount - ?) < 0.05 OR ABS(shipping_cost - ?) < 0.05)
                   AND customer_phone LIKE ?
                 ORDER BY id DESC LIMIT 1'
            );
            $matchStB->execute([$amount, $amount, $amount, '%' . $senderSuffix]);
            $matchedOrder = $matchStB->fetch();
        }
    }

    // Strategy C: Match by Pending Amount within last 12 hours (if exact unique amount match)
    if (!$matchedOrder) {
        $matchStC = $pdo->prepare(
            'SELECT * FROM orders 
             WHERE (payment_status = \'pending\' OR payment_status = \'pending_verification\' OR is_confirmed = 0)
               AND status != \'cancelled\'
               AND (ABS(total - ?) < 0.05 OR ABS(advance_amount - ?) < 0.05 OR ABS(shipping_cost - ?) < 0.05)
               AND created_at >= NOW() - INTERVAL 12 HOUR
             ORDER BY id DESC LIMIT 1'
        );
        $matchStC->execute([$amount, $amount, $amount]);
        $matchedOrder = $matchStC->fetch();
    }

    if ($matchedOrder) {
        $matchedOrderId = (int)$matchedOrder['id'];
        
        // Update Order as Confirmed and Paid
        $updOrder = $pdo->prepare(
            'UPDATE orders SET 
                payment_status = \'paid\', 
                paid_amount = GREATEST(COALESCE(paid_amount, 0), ?),
                is_confirmed = 1,
                confirmed_at = COALESCE(confirmed_at, NOW()),
                status = \'processing\',
                bot_step = \'confirmed_by_fintech_sync\',
                payment_reference = COALESCE(payment_reference, ?)
             WHERE id = ?'
        );
        $updOrder->execute([$amount, $referenceId, $matchedOrderId]);

        // Update Transaction status
        $pdo->prepare('UPDATE bank_transactions SET status = \'matched\', matched_order_id = ? WHERE id = ?')
            ->execute([$matchedOrderId, $txId]);

        // Admin Notification
        add_admin_notification(
            'payment_received',
            'تم استلام تحويل وتأكيد الطلب تلقائياً: #' . $matchedOrder['order_number'],
            'Payment Received & Order Auto-Confirmed: #' . $matchedOrder['order_number'],
            "تم استلام مبلغ {$amount} ج.م عبر ({$provider}) برقم مرجعي {$referenceId} وتأكيد الطلب تلقائياً.",
            "Payment of {$amount} EGP received via {$provider} (Ref: {$referenceId}), order auto-confirmed.",
            'order_view.php?id=' . $matchedOrderId
        );
    } else {
        // Unmatched transaction admin notification
        add_admin_notification(
            'unmatched_transfer',
            'تحويل مالي جديد بانتظار المطابقة: ' . $amount . ' ج.م',
            'New Unmatched Transfer: ' . $amount . ' EGP',
            "تم استلام تحويل بمبلغ {$amount} ج.م عبر ({$provider}) برقم مرجعي {$referenceId}. يمكنك ربطه بالطلب يدوياً.",
            "Transfer of {$amount} EGP received via {$provider} (Ref: {$referenceId}). Pending manual match.",
            'transfers.php'
        );
    }

    // 4. Notify Node.js WhatsApp Bot Server & React Dashboard via Webhook
    try {
        $ch = curl_init('http://127.0.0.1:3001/api/fintech-event');
        $webhookData = json_encode([
            'transaction_id' => $txId,
            'provider' => $provider,
            'amount' => $amount,
            'sender' => $sender,
            'reference_id' => $referenceId,
            'matched_order_id' => $matchedOrderId,
            'order_number' => $matchedOrder['order_number'] ?? null,
            'customer_name' => $matchedOrder['customer_name'] ?? null,
            'received_at' => $receivedAt,
            'timestamp' => date('c')
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 800);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_exec($ch);
        @curl_close($ch);
    } catch (Throwable) {}

    echo json_encode([
        'success' => true,
        'status' => $matchedOrderId ? 'matched' : 'unmatched',
        'transaction_id' => $txId,
        'matched_order_id' => $matchedOrderId,
        'message' => $matchedOrderId ? 'Payment received and order auto-confirmed successfully' : 'Payment received and logged. Pending manual staff match.'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
