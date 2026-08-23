<?php
declare(strict_types=1);

/**
 * Official Meta WhatsApp Cloud API Webhook Handler for Zei Perfumes
 * Handles Verification (GET) and Inbound Messages, Button Clicks, & Media (POST)
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/whatsapp_helper.php';

$cfg = get_wa_cloud_config();
$pdo = medal_pdo();

// ══════════════════════════════════════════════════════════
// 1. Meta Webhook Verification (GET Request)
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $cfg['verify_token']) {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Forbidden: Invalid verification token';
    exit;
}

// ══════════════════════════════════════════════════════════
// 2. Incoming Events & Messages from Customers (POST Request)
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode((string)$rawInput, true);

    // Meta sends 200 OK fast acknowledgement
    http_response_code(200);
    echo 'EVENT_RECEIVED';

    if (!$payload || empty($payload['entry'])) {
        exit;
    }

    try {
        foreach ($payload['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                
                // Process only messages (ignore status updates like delivered/read)
                $messages = $value['messages'] ?? [];
                if (empty($messages)) {
                    continue;
                }

                $contact = $value['contacts'][0] ?? [];
                $profileName = $contact['profile']['name'] ?? 'عميلنا العزيز';

                foreach ($messages as $msg) {
                    $senderPhone = $msg['from'] ?? '';
                    $msgType = $msg['type'] ?? 'text';
                    $msgId = $msg['id'] ?? '';

                    if (empty($senderPhone)) {
                        continue;
                    }

                    // Format phone for database lookup
                    $cleanPhone = preg_replace('/\D/', '', $senderPhone);
                    $localPhone = $cleanPhone;
                    if (str_starts_with($cleanPhone, '20')) {
                        $localPhone = '0' . substr($cleanPhone, 2);
                    }

                    // Find latest active order for this customer
                    $order = null;
                    if ($pdo) {
                        $st = $pdo->prepare("SELECT * FROM orders WHERE (customer_phone LIKE ? OR customer_phone LIKE ?) ORDER BY id DESC LIMIT 1");
                        $st->execute(['%' . substr($cleanPhone, -8), '%' . substr($localPhone, -8)]);
                        $order = $st->fetch();

                        if (!$order) {
                            $st2 = $pdo->query("SELECT * FROM orders WHERE is_confirmed = 0 ORDER BY id DESC LIMIT 1");
                            $order = $st2->fetch();
                        }
                    }

                    $orderId = $order['id'] ?? null;
                    $orderNumber = $order['order_number'] ?? ($orderId ? "MED-{$orderId}" : 'طلبك');
                    $customerName = $order['customer_name'] ?? $profileName;
                    $total = (float)($order['total'] ?? 0);
                    $shippingCost = (float)($order['shipping_cost'] ?? 50);
                    $remainingAmount = max(0, $total - $shippingCost);

                    $instapayUser = $cfg['instapay_user'];
                    $instapayUrl = $cfg['instapay_url'];
                    $vodafoneCash = $cfg['vodafone_cash'];

                    // ── Case A: Customer Clicked an Interactive Button / Template Quick Reply ──
                    if ($msgType === 'interactive' || $msgType === 'button') {
                        $buttonId = '';
                        $buttonTitle = '';

                        if ($msgType === 'interactive') {
                            $buttonId = $msg['interactive']['button_reply']['id'] ?? '';
                            $buttonTitle = $msg['interactive']['button_reply']['title'] ?? '';
                        } elseif ($msgType === 'button') {
                            $buttonId = $msg['button']['payload'] ?? '';
                            $buttonTitle = $msg['button']['text'] ?? '';
                        }

                        $isPayFull = str_contains(strtolower($buttonId), 'pay_full') || str_contains($buttonTitle, 'المبلغ كامل') || str_contains($buttonTitle, 'كامل');
                        $isPayDeposit = str_contains(strtolower($buttonId), 'pay_deposit') || str_contains($buttonTitle, 'العربون');
                        $isConfirm = $isPayFull || $isPayDeposit || str_contains(strtolower($buttonId), 'confirm') || str_contains($buttonTitle, 'تأكيد');
                        $isCancel = str_contains(strtolower($buttonId), 'cancel') || str_contains($buttonTitle, 'إلغاء');

                        // 1. Confirm / Pay Buttons Clicked
                        if ($isConfirm) {
                            $scope = $isPayDeposit ? 'shipping_only' : 'full';
                            if ($pdo && $orderId) {
                                $upd = $pdo->prepare("UPDATE orders SET is_confirmed = 1, confirmed_at = NOW(), bot_step = 'awaiting_receipt', payment_scope = ?, payment_status = 'pending_payment' WHERE id = ?");
                                $upd->execute([$scope, $orderId]);
                            }

                            $amountToPay = $isPayDeposit ? $shippingCost : $total;
                            $amountLabel = $isPayDeposit ? "عربون الشحن (" . number_format($shippingCost, 2) . " ج.م)" : "المبلغ كامل (" . number_format($total, 2) . " ج.م)";

                            $payPrompt = 
"🌸 *شكراً لتأكيد طلبك أ/ {$customerName}!* 🌸
📦 طلب رقم: *#{$orderNumber}*
💵 المطلوب تحويله: *{$amountLabel}*
─────────────────────
💳 *بيانات التحويل:*
• إنستاباي: `{$instapayUser}`
• فودافون كاش: `{$vodafoneCash}`

📸 *أرسل صورة إيصال التحويل هنا لتأكيد الحجز وبدء الشحن فوراً!* ✨";

                            sendTextMessage($senderPhone, $payPrompt);
                            continue;
                        }

                        // 2. Cancel Order Button Clicked
                        if ($isCancel) {
                            if ($pdo && $orderId) {
                                $upd = $pdo->prepare("UPDATE orders SET is_confirmed = 0, status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
                                $upd->execute([$orderId]);
                            }

                            $cancelMsg = 
"❌ *تم إلغاء طلبك رقم ({$orderNumber}) بنجاح.*

نتمنى رؤيتك مجدداً قريباً في *متجر زين للعطور*! 🌸
يمكنك تصفح أحدث العروض والمنتجات دائماً عبر موقعنا:
🌐 *https://zeinperfumes.com*";

                            sendTextMessage($senderPhone, $cancelMsg);
                            continue;
                        }
                    }

                    // ── Case B: Customer Sent a Payment Receipt Image ──
                    if ($msgType === 'image') {
                        $mediaId = $msg['image']['id'] ?? '';
                        $savedFilename = downloadReceipt($mediaId);

                        if ($pdo && $orderId && $savedFilename) {
                            $upd = $pdo->prepare("UPDATE orders SET payment_receipt = ?, is_confirmed = 1, bot_step = 'receipt_received', payment_status = 'pending_verification' WHERE id = ?");
                            $upd->execute([$savedFilename, $orderId]);
                        }

                        $receiptReply = 
"✅ *تم استلام صورة التحويل بنجاح!*

📦 طلب رقم: *{$orderNumber}*
⏳ الحالة: *في انتظار مراجعة وتأكيد التحويل من خدمة العملاء.*

🌸 سيصلك إشعار فوري هنا على الواتساب فور اعتماد الدفع وبدء تجهيز شحنتك. شكراً لاختيارك *زين للعطور*! ✨";

                        sendTextMessage($senderPhone, $receiptReply);
                        continue;
                    }

                    // ── Case C: Customer Sent Text Message (1, 2, Reference Code, Inquiries) ──
                    if ($msgType === 'text') {
                        $rawText = trim($msg['text']['body'] ?? '');
                        $textLower = strtolower($rawText);

                        // If user sent "1" or "تأكيد"
                        if ($textLower === '1' || $textLower === '١' || str_contains($textLower, 'تأكيد') || str_contains($textLower, 'تاكيد')) {
                            if ($pdo && $orderId) {
                                $upd = $pdo->prepare("UPDATE orders SET is_confirmed = 1, confirmed_at = NOW(), bot_step = 'awaiting_receipt', payment_status = 'pending_payment' WHERE id = ?");
                                $upd->execute([$orderId]);
                            }

                            $payPrompt = 
"👑 *شكراً لتأكيد طلبك يا أ/ {$customerName}!* 🌸
📦 بخصوص طلب رقم: *{$orderNumber}*
💰 إجمالي المبلغ: *" . number_format($total, 2) . " ج.م*

🟣 *إنستاباي (InstaPay):*
▫️ المعرّف: `{$instapayUser}`
🔗 *رابط الدفع المباشر بنقرة واحدة:*
{$instapayUrl}

🔴 *محفظة كاش (فودافون كاش / اتصالات / أورانج / وي):*
▫️ الرقم: `{$vodafoneCash}`
─────────────────────
💵 *المطلوب لتحويل الشحن:* *" . number_format($shippingCost, 2) . " ج.م*
🚚 *المتبقي عند الاستلام:* *" . number_format($remainingAmount, 2) . " ج.م*

📸 *لتأكيد الحجز فوراً:*
يرجى إرسال *صورة إيصال التحويل (Screenshot)* 📸 هنا في الشات.";

                            sendTextMessage($senderPhone, $payPrompt);
                            continue;
                        }

                        // If user sent "2" or "إلغاء"
                        if ($textLower === '2' || $textLower === '٢' || str_contains($textLower, 'إلغاء') || str_contains($textLower, 'الغاء')) {
                            if ($pdo && $orderId) {
                                $upd = $pdo->prepare("UPDATE orders SET is_confirmed = 0, status = 'cancelled' WHERE id = ?");
                                $upd->execute([$orderId]);
                            }

                            $cancelMsg = "❌ *تم إلغاء طلبك رقم ({$orderNumber}) بنجاح.* نتمنى رؤيتك مجدداً في متجر زين للعطور! 🌸";
                            sendTextMessage($senderPhone, $cancelMsg);
                            continue;
                        }

                        // Detect transaction / reference number
                        $isReference = preg_match('/\b([a-zA-Z0-9]{6,25})\b/', $rawText, $matches);
                        if ($isReference && $orderId) {
                            $refCode = $matches[1];
                            if ($pdo) {
                                $upd = $pdo->prepare("UPDATE orders SET is_confirmed = 1, bot_step = 'receipt_received', payment_status = 'pending_verification' WHERE id = ?");
                                $upd->execute([$orderId]);
                            }

                            $refReply = 
"✅ *تم استلام رقم العملية بنجاح!*

📦 طلب رقم: *{$orderNumber}*
🔢 رقم العملية: *{$refCode}*
⏳ الحالة: *في انتظار مراجعة وتأكيد التحويل من خدمة العملاء.*

🌸 شكراً لاختيارك *زين للعطور*! ✨";

                            sendTextMessage($senderPhone, $refReply);
                            continue;
                        }
                    }

                }
            }
        }
    } catch (\Throwable $e) {
        error_log('Webhook processing error: ' . $e->getMessage());
    }

    exit;
}
