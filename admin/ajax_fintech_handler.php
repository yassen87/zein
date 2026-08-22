<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/receipt_ocr_matcher.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = medal_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$action = trim((string)($_REQUEST['action'] ?? ''));

// 1. Fetch live transfers
if ($action === 'fetch_live') {
    $st = $pdo->query('
        SELECT bt.*, 
               o.order_number, o.customer_name, o.customer_phone, o.total as order_total, 
               o.payment_receipt, o.ocr_status, o.payment_status as order_payment_status,
               o.status as order_status
        FROM bank_transactions bt
        LEFT JOIN orders o ON bt.matched_order_id = o.id
        ORDER BY bt.id DESC 
        LIMIT 50
    ');
    $transactions = $st->fetchAll();

    // Stats
    $statsSt = $pdo->query('
        SELECT 
            COUNT(*) as total_transfers,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(CASE WHEN status = "matched" THEN 1 END) as matched_count,
            COUNT(CASE WHEN status = "unmatched" THEN 1 END) as unmatched_count
        FROM bank_transactions
    ');
    $stats = $statsSt->fetch();

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'transactions' => $transactions
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Simulate Test Transfer (بدون فلوس حقيقية للتجربة والاختبار)
if ($action === 'simulate_test_transfer') {
    $provider = trim((string)($_POST['provider'] ?? 'vodafone_cash'));
    $amount = (float)($_POST['amount'] ?? 150.0);
    $sender = trim((string)($_POST['sender'] ?? '01005250838'));
    $refId = trim((string)($_POST['reference_id'] ?? ''));
    $linkOrderId = isset($_POST['target_order_id']) && (int)$_POST['target_order_id'] > 0 ? (int)$_POST['target_order_id'] : null;

    if ($refId === '') {
        $prefix = ($provider === 'vodafone_cash') ? 'VF' : (($provider === 'instapay') ? 'IPA' : 'TX');
        $refId = $prefix . '-' . time() . '-' . rand(1000, 9999);
    }

    $rawSms = '';
    if ($provider === 'vodafone_cash') {
        $rawSms = "تم استلام مبلغ {$amount} ج.م من رقم {$sender}. الرقم المرجعي للعملية هو {$refId}. رصيدك الحالي هو 5420.50 ج.م.";
    } elseif ($provider === 'instapay') {
        $rawSms = "تم استلام تحويل لحظي IPN بمبلغ {$amount} EGP من {$sender}. الرقم المرجعي: {$refId}. تم إضافة المبلغ لحسابك بنجاح.";
    } elseif ($provider === 'orange_cash') {
        $rawSms = "تم استلام مبلغ {$amount} جنيه من محفظة {$sender}. كود العملية: {$refId}.";
    } else {
        $rawSms = "تحويل بنكي وارد بمبلغ {$amount} EGP من {$sender}. رقم المعاملة {$refId}.";
    }

    // Insert into bank_transactions
    $ins = $pdo->prepare('
        INSERT INTO bank_transactions (provider, amount, sender_number_or_handle, reference_id, raw_message, status, received_at)
        VALUES (?, ?, ?, ?, ?, "unmatched", NOW())
    ');
    $ins->execute([$provider, $amount, $sender, $refId, $rawSms]);
    $txId = (int)$pdo->lastInsertId();

    $matchedOrder = null;

    // If specific order targeted
    if ($linkOrderId) {
        $stOrd = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stOrd->execute([$linkOrderId]);
        $matchedOrder = $stOrd->fetch();
    } else {
        // Auto-match Strategy: by sender phone or amount in pending orders
        $senderDigits = preg_replace('/\D/', '', $sender);
        if (strlen($senderDigits) >= 9) {
            $senderSuffix = substr($senderDigits, -9);
            $stMatch = $pdo->prepare('
                SELECT * FROM orders 
                WHERE (payment_status = "pending" OR payment_status = "pending_verification" OR is_confirmed = 0)
                  AND status != "cancelled"
                  AND customer_phone LIKE ?
                ORDER BY id DESC LIMIT 1
            ');
            $stMatch->execute(['%' . $senderSuffix]);
            $matchedOrder = $stMatch->fetch();
        }

        if (!$matchedOrder) {
            // Match by exact amount
            $stAmt = $pdo->prepare('
                SELECT * FROM orders 
                WHERE (payment_status = "pending" OR payment_status = "pending_verification" OR is_confirmed = 0)
                  AND status != "cancelled"
                  AND (ABS(total - ?) < 0.05 OR ABS(shipping_cost - ?) < 0.05)
                ORDER BY id DESC LIMIT 1
            ');
            $stAmt->execute([$amount, $amount]);
            $matchedOrder = $stAmt->fetch();
        }
    }

    if ($matchedOrder) {
        $ordId = (int)$matchedOrder['id'];
        // Update Order
        $pdo->prepare('
            UPDATE orders SET 
                payment_status = "paid",
                paid_amount = ?,
                payment_reference = ?,
                is_confirmed = 1,
                confirmed_at = NOW(),
                status = "processing",
                ocr_status = "matched",
                bot_step = "verified"
            WHERE id = ?
        ')->execute([$amount, $refId, $ordId]);

        // Update Transaction
        $pdo->prepare('UPDATE bank_transactions SET status = "matched", matched_order_id = ?, matched_at = NOW(), ocr_matched_at = NOW() WHERE id = ?')
            ->execute([$ordId, $txId]);

        // Admin Notification
        add_admin_notification(
            'payment_received',
            '⚡️ تحويل تجريبي مطابق للطلب #' . $matchedOrder['order_number'],
            '⚡️ Test Transfer Matched to Order #' . $matchedOrder['order_number'],
            "تم تسجيل تحويل تجريبي بمبلغ {$amount} ج.م ومطابقته آلياً مع الطلب #{$matchedOrder['order_number']}",
            "Test transfer of {$amount} EGP matched to order #{$matchedOrder['order_number']}",
            'fintech_transfers.php'
        );

        echo json_encode([
            'success' => true,
            'matched' => true,
            'transaction_id' => $txId,
            'reference_id' => $refId,
            'order_id' => $ordId,
            'order_number' => $matchedOrder['order_number'],
            'message' => "تم تسجيل التحويل التجريبي بنجاح ومطابقته فوراً مع الطلب #{$matchedOrder['order_number']} وتأكيده!"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'matched' => false,
        'transaction_id' => $txId,
        'reference_id' => $refId,
        'message' => "تم تسجيل التحويل التجريبي بنجاح ({$amount} ج.م) ويظهر الآن في قائمة التحويلات الواردة بانتظار الربط."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Manual Match Transfer with an Order
if ($action === 'manual_match') {
    $txId = (int)($_POST['transaction_id'] ?? 0);
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($txId <= 0 || $orderId <= 0) {
        echo json_encode(['success' => false, 'error' => 'بيانات غير مكتملة']);
        exit;
    }

    $stTx = $pdo->prepare('SELECT * FROM bank_transactions WHERE id = ?');
    $stTx->execute([$txId]);
    $tx = $stTx->fetch();

    $stOrd = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stOrd->execute([$orderId]);
    $order = $stOrd->fetch();

    if (!$tx || !$order) {
        echo json_encode(['success' => false, 'error' => 'العملية أو الطلب غير موجود']);
        exit;
    }

    $amount = (float)$tx['amount'];
    $refId = $tx['reference_id'];

    // Update order
    $pdo->prepare('
        UPDATE orders SET 
            payment_status = "paid",
            paid_amount = ?,
            payment_reference = ?,
            is_confirmed = 1,
            confirmed_at = NOW(),
            status = "processing",
            ocr_status = "matched",
            bot_step = "verified_manual"
        WHERE id = ?
    ')->execute([$amount, $refId, $orderId]);

    // Update transaction
    $pdo->prepare('UPDATE bank_transactions SET status = "matched", matched_order_id = ?, matched_at = NOW() WHERE id = ?')
        ->execute([$orderId, $txId]);

    echo json_encode([
        'success' => true,
        'message' => "تم ربط التحويل بنجاح مع الطلب #{$order['order_number']} وتأكيد الدفع!"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Delete / Ignore Transaction
if ($action === 'delete_transaction') {
    $txId = (int)($_POST['transaction_id'] ?? 0);
    if ($txId > 0) {
        $pdo->prepare('DELETE FROM bank_transactions WHERE id = ?')->execute([$txId]);
        echo json_encode(['success' => true, 'message' => 'تم حذف العملية بنجاح']);
        exit;
    }
}

// 5. Search orders for manual matching modal
if ($action === 'search_orders') {
    $q = trim((string)($_GET['q'] ?? ''));
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'orders' => []]);
        exit;
    }

    $st = $pdo->prepare('
        SELECT id, order_number, customer_name, customer_phone, total, payment_status, status, created_at
        FROM orders 
        WHERE order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?
        ORDER BY id DESC LIMIT 15
    ');
    $term = "%{$q}%";
    $st->execute([$term, $term, $term]);
    $orders = $st->fetchAll();

    echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
