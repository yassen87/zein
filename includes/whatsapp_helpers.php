<?php
declare(strict_types=1);

/**
 * Official Meta WhatsApp Cloud API Helper Functions for Zei Perfumes
 */

require_once __DIR__ . '/db.php';

// Default Meta Cloud API Configuration
if (!defined('WA_API_VERSION')) {
    define('WA_API_VERSION', 'v20.0');
}
if (!defined('WA_VERIFY_TOKEN')) {
    define('WA_VERIFY_TOKEN', 'MySecretToken123');
}
if (!defined('WA_PHONE_NUMBER_ID')) {
    define('WA_PHONE_NUMBER_ID', 'YOUR_PHONE_NUMBER_ID');
}
if (!defined('WA_ACCESS_TOKEN')) {
    define('WA_ACCESS_TOKEN', 'YOUR_PERMANENT_ACCESS_TOKEN');
}

/**
 * Get dynamic WhatsApp Cloud API configuration from database settings or constants
 */
function get_wa_cloud_config(): array
{
    $config = [
        'phone_number_id' => WA_PHONE_NUMBER_ID,
        'access_token' => WA_ACCESS_TOKEN,
        'verify_token' => WA_VERIFY_TOKEN,
        'api_version' => WA_API_VERSION,
        'instapay_user' => 'ahmedfayoumy1@instapay',
        'instapay_url' => 'https://ipn.eg/S/ahmedfayoumy1/instapay/7H0dWv',
        'vodafone_cash' => '01005250838',
    ];

    $pdo = medal_pdo();
    if ($pdo) {
        try {
            $st = $pdo->query("SELECT setting_key, setting_value_ar, setting_value_en FROM settings WHERE setting_key IN ('wa_phone_number_id', 'wa_access_token', 'wa_verify_token', 'instapay_username', 'instapay_url', 'vodafone_cash_number')");
            $rows = $st->fetchAll();
            foreach ($rows as $r) {
                $val = !empty($r['setting_value_ar']) ? $r['setting_value_ar'] : ($r['setting_value_en'] ?? '');
                if (!empty($val)) {
                    if ($r['setting_key'] === 'wa_phone_number_id') $config['phone_number_id'] = $val;
                    if ($r['setting_key'] === 'wa_access_token') $config['access_token'] = $val;
                    if ($r['setting_key'] === 'wa_verify_token') $config['verify_token'] = $val;
                    if ($r['setting_key'] === 'instapay_username') $config['instapay_user'] = $val;
                    if ($r['setting_key'] === 'instapay_url') $config['instapay_url'] = $val;
                    if ($r['setting_key'] === 'vodafone_cash_number') $config['vodafone_cash'] = $val;
                }
            }
        } catch (\Throwable $e) {
            error_log('Error loading WA cloud config: ' . $e->getMessage());
        }
    }

    return $config;
}

/**
 * Format phone number to international E.164 without leading + or 00
 */
function format_wa_phone(string $phone): string
{
    $clean = preg_replace('/\D/', '', $phone);
    $clean = preg_replace('/^00/', '', $clean);

    if (str_starts_with($clean, '01') && strlen($clean) === 11) {
        $clean = '2' . $clean;
    } elseif (str_starts_with($clean, '1') && strlen($clean) === 10) {
        $clean = '20' . $clean;
    } elseif (str_starts_with($clean, '05') && strlen($clean) === 10) {
        $clean = '966' . substr($clean, 1);
    }

    return $clean;
}

/**
 * Send interactive Order Confirmation message with direct Clickable Buttons
 * ("تأكيد الطلب" and "إلغاء الطلب")
 */
function sendInteractiveOrderButtons(string $phone, int|string $order_id, string $order_number, float|int $amount, string $customer_name = 'عميلنا العزيز', float|int $shipping_cost = 0): array
{
    $cfg = get_wa_cloud_config();
    $recipient = format_wa_phone($phone);

    $bodyText = "🌸 *أهلاً بك أ/ {$customer_name} في زين للعطور!* 🌸\n\n"
              . "📦 طلب رقم: *#{$order_number}*\n"
              . "💰 إجمالي المبلغ: *" . number_format((float)$amount, 2) . " ج.م*" . ($shipping_cost > 0 ? " (شامل الشحن " . number_format((float)$shipping_cost, 2) . " ج.م)" : "") . "\n"
              . "─────────────────────\n"
              . "اختر طريقة السداد المفضلة بالضغط على أحد الأزرار:";

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $recipient,
        'type' => 'interactive',
        'interactive' => [
            'type' => 'button',
            'header' => [
                'type' => 'text',
                'text' => '🌸 تأكيد طلب متجر زين للعطور'
            ],
            'body' => [
                'text' => $bodyText
            ],
            'footer' => [
                'text' => 'زين للعطور - Zein Perfumes'
            ],
            'action' => [
                'buttons' => [
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => 'btn_pay_full_' . $order_id,
                            'title' => '💰 دفع المبلغ كامل'
                        ]
                    ],
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => 'btn_pay_deposit_' . $order_id,
                            'title' => '🚚 دفع العربون فقط'
                        ]
                    ],
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => 'btn_cancel_' . $order_id,
                            'title' => '❌ إلغاء الطلب'
                        ]
                    ]
                ]
            ]
        ]
    ];

    return sendMetaCloudRequest($payload);
}

/**
 * Send pre-approved WhatsApp Template Message with variables and interactive buttons
 */
function sendOrderTemplate(string $phone, int|string $order_id, float|int $amount, string $customer_name = 'عميلنا العزيز', string $template_name = 'order_confirmation'): array
{
    $cfg = get_wa_cloud_config();
    $recipient = format_wa_phone($phone);

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $recipient,
        'type' => 'template',
        'template' => [
            'name' => $template_name,
            'language' => [
                'code' => 'ar'
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $customer_name],
                        ['type' => 'text', 'text' => (string)$order_id],
                        ['type' => 'text', 'text' => number_format((float)$amount, 2) . ' ج.م']
                    ]
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'btn_confirm_' . $order_id]
                    ]
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '1',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'btn_cancel_' . $order_id]
                    ]
                ]
            ]
        ]
    ];

    // Attempt template send; if template is not yet approved or fails, gracefully fallback to interactive buttons
    $res = sendMetaCloudRequest($payload);
    if (!$res['success']) {
        return sendInteractiveOrderButtons($phone, $order_id, (string)$order_id, $amount, $customer_name);
    }
    return $res;
}

/**
 * Send standard plain text message via WhatsApp Cloud API
 */
function sendTextMessage(string $phone, string $text): array
{
    $recipient = format_wa_phone($phone);

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $recipient,
        'type' => 'text',
        'text' => [
            'preview_url' => true,
            'body' => $text
        ]
    ];

    return sendMetaCloudRequest($payload);
}

/**
 * Download customer's uploaded receipt image from Meta Cloud API servers
 * and save it into /assets/uploads/receipts/
 */
function downloadReceipt(string $media_id): ?string
{
    if (empty($media_id)) {
        return null;
    }

    $cfg = get_wa_cloud_config();
    $token = $cfg['access_token'];
    $version = $cfg['api_version'];

    // 1. Get media URL from Meta Graph API
    $getUrl = "https://graph.facebook.com/{$version}/{$media_id}";
    $ch = curl_init($getUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}"
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$res) {
        error_log("Failed to fetch media metadata for ID {$media_id}. Code: {$httpCode}");
        return null;
    }

    $mediaData = json_decode((string)$res, true);
    $downloadUrl = $mediaData['url'] ?? null;
    $mimeType = $mediaData['mime_type'] ?? 'image/jpeg';

    if (!$downloadUrl) {
        return null;
    }

    // Determine extension
    $ext = 'jpg';
    if (str_contains($mimeType, 'png')) $ext = 'png';
    elseif (str_contains($mimeType, 'webp')) $ext = 'webp';

    // 2. Download the binary media file from Meta CDN
    $ch2 = curl_init($downloadUrl);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            "User-Agent: ZeinPerfumesBot/1.0"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $binaryData = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($httpCode2 !== 200 || !$binaryData) {
        error_log("Failed to download media binary for ID {$media_id}");
        return null;
    }

    // Save to receipts directory
    $receiptsDir = __DIR__ . '/../assets/uploads/receipts';
    if (!is_dir($receiptsDir)) {
        @mkdir($receiptsDir, 0777, true);
    }

    $filename = 'receipt_cloud_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $receiptsDir . '/' . $filename;

    if (file_put_contents($targetPath, $binaryData) === false) {
        error_log("Failed to write receipt file to: {$targetPath}");
        return null;
    }

    return $filename;
}

/**
 * Internal helper to send cURL requests to Meta Graph API
 */
function sendMetaCloudRequest(array $payload): array
{
    $cfg = get_wa_cloud_config();
    $phoneNumberId = $cfg['phone_number_id'];
    $token = $cfg['access_token'];
    $version = $cfg['api_version'];

    $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("WA Cloud cURL error: " . $curlErr);
        return ['success' => false, 'error' => $curlErr];
    }

    $resArr = json_decode((string)$response, true);
    $success = ($httpCode >= 200 && $httpCode < 300 && empty($resArr['error']));

    if (!$success) {
        error_log("WA Cloud API error (HTTP {$httpCode}): " . json_encode($resArr, JSON_UNESCAPED_UNICODE));
    }

    return [
        'success' => $success,
        'http_code' => $httpCode,
        'data' => $resArr,
        'error' => $resArr['error']['message'] ?? ($success ? null : 'Unknown Meta API error')
    ];
}
