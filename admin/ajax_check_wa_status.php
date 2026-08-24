<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = medal_pdo();
$botUrl = 'https://wa.zeinperfumes.com/api/status';

if ($pdo !== null) {
    try {
        $st = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
        $st->execute();
        $customUrl = $st->fetchColumn();
        if (!empty($customUrl)) {
            $botUrl = rtrim($customUrl, '/') . '/api/status';
        }
    } catch (\Throwable $e) {}
}

$ch = curl_init($botUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200 || !$response) {
    echo json_encode([
        'connected' => false,
        'status' => 'disconnected',
        'error' => $curlErr ?: "HTTP Code $httpCode",
        'bot_url' => $botUrl
    ]);
    exit;
}

$data = json_decode((string)$response, true);
$isReady = ($data && isset($data['status']) && $data['status'] === 'ready');

echo json_encode([
    'connected' => $isReady,
    'status' => $data['status'] ?? 'unknown',
    'info' => $data['info'] ?? null,
    'uptime' => $data['uptime'] ?? 0,
    'bot_url' => $botUrl
]);
