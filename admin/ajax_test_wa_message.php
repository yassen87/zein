<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_admin('settings');

header('Content-Type: application/json; charset=utf-8');

$phone = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($phone === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'يرجى كتابة رقم الهاتف ومحتوى الرسالة.']);
    exit;
}

admin_verify_csrf();

$pdo = medal_pdo();
$botUrl = 'https://wa.zeinperfumes.com/api/test-message';

if ($pdo !== null) {
    try {
        $st = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
        $st->execute();
        $customUrl = $st->fetchColumn();
        if (!empty($customUrl)) {
            $botUrl = rtrim($customUrl, '/') . '/api/test-message';
        }
    } catch (\Throwable $e) {}
}

$payload = json_encode([
    'phone' => $phone,
    'message' => $message
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($botUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ],
    CURLOPT_TIMEOUT => 6,
    CURLOPT_CONNECTTIMEOUT => 3,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'error' => $curlErr ?: "خطأ في السيرفر الرئيسي (HTTP Code $httpCode)",
        'response' => $response
    ]);
    exit;
}

$data = json_decode((string)$response, true);
echo json_encode([
    'success' => $data['success'] ?? false,
    'message' => $data['message'] ?? 'تم إرسال الطلب بنجاح',
    'result' => $data
]);
