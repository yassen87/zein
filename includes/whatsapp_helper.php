<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Send Automated Order Confirmation Menu (1, 2, 3) to customer via WhatsApp Bot Microservice.
 * Falls back gracefully to building direct wa.me link if microservice is offline.
 */
function send_whatsapp_order_bot_notification(int $orderId, string $orderNumber, string $customerName, string $phone, float $total, array $orderLines = [], float $shippingCost = 0.0): array
{
    $pdo = medal_pdo();
    $botUrl = 'http://127.0.0.1:3001/api/send-order';

    if ($pdo !== null) {
        try {
            $st = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
            $st->execute();
            $customUrl = $st->fetchColumn();
            if (!empty($customUrl)) {
                $botUrl = rtrim($customUrl, '/') . '/api/send-order';
            }
        } catch (\Throwable $e) {
            error_log('Error loading whatsapp_bot_url setting: ' . $e->getMessage());
        }
    }

    $payload = [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'customer_name' => $customerName,
        'customer_phone' => $phone,
        'total' => $total,
        'shipping_cost' => $shippingCost,
        'lines' => $orderLines
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // Prepare direct fallback wa.me URL
    $fallbackMsg = "🌸 أهلاً بك يا أ/ {$customerName} في متجر زين للعطور 🌸\n\n";
    $fallbackMsg .= "📦 تم تسجيل طلبك بنجاح برقم: *{$orderNumber}*\n";
    $fallbackMsg .= "💰 إجمالي المبلغ المطلوب: *" . number_format($total, 2) . " ج.م*\n\n";
    $fallbackMsg .= "يرجى الرد برقم الخيار لتأكيد طلبك:\n";
    $fallbackMsg .= "1️⃣ - *تأكيد الطلب*\n";
    $fallbackMsg .= "2️⃣ - *إلغاء الطلب*\n";
    $fallbackMsg .= "3️⃣ - *تعديل الطلب من على الموقع*\n";

    $fallbackWaUrl = contact_whatsapp_url(1) . '?text=' . urlencode($fallbackMsg);

    // Call Node.js Bot Service
    $ch = curl_init($botUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonPayload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Fast non-blocking timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $success = ($httpCode === 200);

    if ($pdo !== null && $success) {
        try {
            $upd = $pdo->prepare("UPDATE orders SET wa_conf_sent = 1, bot_step = 'menu' WHERE id = ?");
            $upd->execute([$orderId]);
        } catch (\Throwable $e) {}
    }

    return [
        'bot_sent' => $success,
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError,
        'fallback_wa_url' => $fallbackWaUrl,
        'wa_message' => $fallbackMsg
    ];
}

/**
 * Get store payment settings for InstaPay & Wallets
 */
function get_payment_settings(): array
{
    $defaults = [
        'instapay_username' => 'zain@instapay',
        'vodafone_cash_number' => '01111026600',
        'bank_account_info' => 'البنك الأهلي المصري - حساب رقم 123456789 - آيبان: EG123456',
        'whatsapp_bot_url' => 'http://127.0.0.1:3001',
        'whatsapp_bot_phone' => '201111026600'
    ];

    $pdo = medal_pdo();
    if ($pdo === null) {
        return $defaults;
    }

    try {
        $st = $pdo->query("SELECT setting_key, setting_value_ar, setting_value_en FROM settings WHERE setting_key IN ('instapay_username', 'vodafone_cash_number', 'bank_account_info', 'whatsapp_bot_url', 'whatsapp_bot_phone')");
        $rows = $st->fetchAll();
        foreach ($rows as $r) {
            $k = $r['setting_key'];
            $val = !empty($r['setting_value_ar']) ? $r['setting_value_ar'] : ($r['setting_value_en'] ?? '');
            if (!empty($val)) {
                $defaults[$k] = $val;
            }
        }
    } catch (\Throwable $e) {
        error_log('Error loading payment settings: ' . $e->getMessage());
    }

    return $defaults;
}

/**
 * Send automated WhatsApp status update notification to customer when status changes on Dashboard.
 */
function send_whatsapp_order_status_notification(int $orderId, string $newStatus): array
{
    $pdo = medal_pdo();
    if ($pdo === null || $orderId < 1) {
        return ['success' => false, 'error' => 'Invalid order or PDO'];
    }

    try {
        $st = $pdo->prepare('SELECT id, order_number, customer_name, customer_phone, total, shipping_cost, payment_scope, advance_amount, remaining_amount, paid_amount FROM orders WHERE id = ?');
        $st->execute([$orderId]);
        $order = $st->fetch();
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $botUrl = 'http://127.0.0.1:3001/api/send-status-update';
        $settingSt = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
        $settingSt->execute();
        $customUrl = $settingSt->fetchColumn();
        if (!empty($customUrl)) {
            $botUrl = rtrim($customUrl, '/') . '/api/send-status-update';
        }

        $payload = json_encode([
            'order_id' => $orderId,
            'status' => $newStatus
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($botUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'success' => ($httpCode === 200),
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError
        ];
    } catch (\Throwable $e) {
        error_log('Error sending status WhatsApp notification: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Broadcast new product announcement to all customers via WhatsApp
 */
function broadcast_whatsapp_new_product(array $productData): array
{
    $pdo = medal_pdo();
    $botUrl = 'http://127.0.0.1:3001/api/broadcast-product';

    if ($pdo !== null) {
        try {
            $settingSt = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
            $settingSt->execute();
            $customUrl = $settingSt->fetchColumn();
            if (!empty($customUrl)) {
                $botUrl = rtrim($customUrl, '/') . '/api/broadcast-product';
            }
        } catch (\Throwable $e) {
            error_log('Error loading whatsapp_bot_url setting: ' . $e->getMessage());
        }
    }

    $payload = json_encode($productData, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($botUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'success' => ($httpCode === 200),
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError
    ];
}
