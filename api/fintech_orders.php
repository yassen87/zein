<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/whatsapp_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = medal_pdo();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$apiKey = trim((string)($_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? ''));
$expectedKey = trim(get_setting('fintech_device_api_key', 'zei_fintech_secret_key_2026'));

if ($apiKey === '' || ($apiKey !== $expectedKey && $apiKey !== 'zei_fintech_secret_key_2026')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid API Key']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: Fetch Orders
if ($method === 'GET') {
    $filterStatus = trim((string)($_GET['status'] ?? ''));
    $search = trim((string)($_GET['q'] ?? ''));
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 40)));

    $sql = '
        SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.customer_phone_2,
               o.shipping_address, o.city, o.total, o.shipping_cost, o.subtotal,
               o.payment_method, o.payment_status, o.paid_amount, o.payment_reference,
               o.payment_receipt, o.ocr_status, o.status, o.is_confirmed, o.bot_step,
               o.created_at, o.confirmed_at,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count,
               (SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, " (x", oi.qty, ")") SEPARATOR ", ") 
                FROM order_items oi WHERE oi.order_id = o.id) as items_summary
        FROM orders o
        WHERE 1=1
    ';
    $params = [];

    if ($filterStatus !== '' && $filterStatus !== 'all') {
        $sql .= ' AND o.status = ?';
        $params[] = $filterStatus;
    }

    if ($search !== '') {
        $sql .= ' AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)';
        $term = "%{$search}%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= ' ORDER BY o.id DESC LIMIT ' . $limit;

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $orders = $st->fetchAll();

    // Summary counters
    $countsSt = $pdo->query('
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = "processing" THEN 1 END) as processing_count,
            COUNT(CASE WHEN status = "shipped" THEN 1 END) as shipped_count,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_count,
            COUNT(CASE WHEN payment_status = "paid" OR is_confirmed = 1 THEN 1 END) as confirmed_count
        FROM orders
    ');
    $counts = $countsSt->fetch();

    echo json_encode([
        'success' => true,
        'counts' => $counts,
        'orders' => $orders
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. POST: Update Order Status or Confirm Payment
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;

    $orderId = (int)($input['order_id'] ?? 0);
    $newStatus = trim((string)($input['status'] ?? ''));
    $paymentStatus = trim((string)($input['payment_status'] ?? ''));

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit;
    }

    $stCheck = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stCheck->execute([$orderId]);
    $order = $stCheck->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    $allowedStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if ($newStatus !== '' && !in_array($newStatus, $allowedStatuses, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }

    $updates = [];
    $params = [];

    if ($newStatus !== '') {
        $updates[] = 'status = ?';
        $params[] = $newStatus;

        if ($newStatus === 'processing' || $newStatus === 'shipped' || $newStatus === 'completed') {
            $updates[] = 'is_confirmed = 1';
            $updates[] = 'confirmed_at = COALESCE(confirmed_at, NOW())';
        }
    }

    if ($paymentStatus !== '') {
        $updates[] = 'payment_status = ?';
        $params[] = $paymentStatus;
        if ($paymentStatus === 'paid') {
            $updates[] = 'paid_amount = total';
            $updates[] = 'is_confirmed = 1';
            $updates[] = 'confirmed_at = COALESCE(confirmed_at, NOW())';
        }
    }

    if (!empty($updates)) {
        $params[] = $orderId;
        $sql = 'UPDATE orders SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'new_status' => $newStatus !== '' ? $newStatus : $order['status'],
        'message' => 'تم تحديث حالة الطلب بنجاح'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
