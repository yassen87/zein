<?php
declare(strict_types=1);

/**
 * High-Precision Fintech OCR Parser & Bank Auto-Reconciliation Engine
 * for Zei Perfumes (Vodafone Cash, InstaPay IPN, Orange Money, Etisalat Cash, Bank Transfers)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/whatsapp_helpers.php';

class ReceiptOcrMatcher
{
    /**
     * Parse raw OCR text extracted from receipt image
     */
    public static function parseOcrText(string $rawText): array
    {
        $text = trim($rawText);
        $cleanText = str_replace(["\r", "\t"], ' ', $text);

        $result = [
            'amount' => null,
            'reference_id' => null,
            'sender' => null,
            'provider' => 'unknown',
            'provider_name' => 'تحويل إلكتروني',
            'confidence' => 0,
        ];

        if (empty($cleanText)) {
            return $result;
        }

        // 1. Detect Provider
        if (stripos($cleanText, 'vodafone') !== false || stripos($cleanText, 'فودافون') !== false || stripos($cleanText, 'VF-Cash') !== false) {
            $result['provider'] = 'vodafone_cash';
            $result['provider_name'] = 'فودافون كاش (Vodafone Cash)';
            $result['confidence'] += 20;
        } elseif (stripos($cleanText, 'instapay') !== false || stripos($cleanText, 'انستاباي') !== false || stripos($cleanText, 'إنستاباي') !== false || stripos($cleanText, 'IPN') !== false) {
            $result['provider'] = 'instapay';
            $result['provider_name'] = 'إنستاباي (InstaPay IPN)';
            $result['confidence'] += 20;
        } elseif (stripos($cleanText, 'orange') !== false || stripos($cleanText, 'اورانج') !== false || stripos($cleanText, 'أورانج') !== false) {
            $result['provider'] = 'orange_cash';
            $result['provider_name'] = 'أورانج كاش (Orange Money)';
            $result['confidence'] += 20;
        } elseif (stripos($cleanText, 'etisalat') !== false || stripos($cleanText, 'اتصالات') !== false || stripos($cleanText, 'e&') !== false) {
            $result['provider'] = 'etisalat_cash';
            $result['provider_name'] = 'اتصالات كاش (e& money)';
            $result['confidence'] += 20;
        }

        // 2. Extract Reference Number
        // Matches: "الرقم المرجعي: 12345678" or "Ref No: IPA-123456" or standalone 8-20 digit codes
        if (preg_match('/(?:الرقم\s*المرجعي|رقم\s*العملية|المرجعي|كود\s*العملية|ref(?:erence)?(?:\s*no)?|trx|tx)[\s:#\-]*([a-zA-Z0-9_\-]{5,30})/i', $cleanText, $mRef)) {
            $result['reference_id'] = trim($mRef[1]);
            $result['confidence'] += 35;
        } elseif (preg_match('/\b([0-9]{8,25})\b/', $cleanText, $mDigits)) {
            $result['reference_id'] = trim($mDigits[1]);
            $result['confidence'] += 20;
        } elseif (preg_match('/\b(IPA[0-9]{6,16}|VF[0-9]{6,16})\b/i', $cleanText, $mAlpha)) {
            $result['reference_id'] = trim($mAlpha[1]);
            $result['confidence'] += 30;
        }

        // 3. Extract Amount (المبلغ)
        // Matches: "المبلغ: 650.00 ج.م" or "EGP 650" or "650 LE" or "650.00 جنيه"
        if (preg_match('/(?:المبلغ|المبلغ\s*المدفوع|المبلغ\s*المحول|القيمة|Amount|Total)?[\s:#\-]*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:ج\.?م|جنيه|جم|EGP|LE)/i', $cleanText, $mAmt)) {
            $val = (float)str_replace(',', '', $mAmt[1]);
            if ($val > 0) {
                $result['amount'] = $val;
                $result['confidence'] += 35;
            }
        } elseif (preg_match('/(?:EGP|LE)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $cleanText, $mAmt2)) {
            $val = (float)str_replace(',', '', $mAmt2[1]);
            if ($val > 0) {
                $result['amount'] = $val;
                $result['confidence'] += 30;
            }
        }

        // 4. Extract Sender Wallet Phone or InstaPay handle
        if (preg_match('/(?:من|من\s*رقم|من\s*محفظة|From)[\s:#\-]*([0-9]{11})/i', $cleanText, $mSendPhone)) {
            $result['sender'] = trim($mSendPhone[1]);
            $result['confidence'] += 10;
        } elseif (preg_match('/(?:من|From)[\s:#\-]*([a-zA-Z0-9._\-]+@instapay)/i', $cleanText, $mSendIpa)) {
            $result['sender'] = trim($mSendIpa[1]);
            $result['confidence'] += 10;
        }

        return $result;
    }

    /**
     * Reconcile an order against the bank_transactions database table
     */
    public static function reconcileOrder(int $orderId, array $ocrData, string $receiptFilename = ''): array
    {
        $pdo = medal_pdo();
        if (!$pdo) {
            return ['matched' => false, 'error' => 'Database connection failed'];
        }

        // Fetch Order
        $stOrd = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stOrd->execute([$orderId]);
        $order = $stOrd->fetch();

        if (!$order) {
            return ['matched' => false, 'error' => 'Order not found'];
        }

        $orderTotal = (float)($order['total'] ?? 0);
        $shippingCost = (float)($order['shipping_cost'] ?? 50);
        $refId = $ocrData['reference_id'] ?? null;
        $amount = $ocrData['amount'] ?? null;
        $sender = $ocrData['sender'] ?? null;

        $matchedTx = null;

        // Strategy 1: Match by exact Reference Number in bank_transactions
        if (!empty($refId)) {
            $st1 = $pdo->prepare('SELECT * FROM bank_transactions WHERE reference_id = ? LIMIT 1');
            $st1->execute([$refId]);
            $matchedTx = $st1->fetch();
        }

        // Strategy 2: Match by Amount + Sender Phone / Handle
        if (!$matchedTx && $amount && $amount > 0 && !empty($sender)) {
            $senderDigits = preg_replace('/\D/', '', $sender);
            $st2 = $pdo->prepare('SELECT * FROM bank_transactions WHERE amount = ? AND (sender_number_or_handle LIKE ? OR sender_number_or_handle LIKE ?) AND (status = \'unmatched\' OR status IS NULL) ORDER BY id DESC LIMIT 1');
            $st2->execute([$amount, '%' . substr($senderDigits, -8), '%' . $sender . '%']);
            $matchedTx = $st2->fetch();
        }

        // Strategy 3: Match by Customer Order Phone + Amount
        if (!$matchedTx && $amount && $amount > 0) {
            $custDigits = preg_replace('/\D/', '', (string)($order['customer_phone'] ?? ''));
            if (strlen($custDigits) >= 9) {
                $st3 = $pdo->prepare('SELECT * FROM bank_transactions WHERE amount = ? AND sender_number_or_handle LIKE ? AND (status = \'unmatched\' OR status IS NULL) ORDER BY id DESC LIMIT 1');
                $st3->execute([$amount, '%' . substr($custDigits, -8)]);
                $matchedTx = $st3->fetch();
            }
        }

        // ── MATCH FOUND ──
        if ($matchedTx) {
            $paidVal = (float)$matchedTx['amount'];
            $isShippingOnly = ($paidVal < $orderTotal && abs($paidVal - $shippingCost) <= 5);
            $payStatus = $isShippingOnly ? 'deposit_paid' : 'verified';

            // Update Order
            $updOrd = $pdo->prepare(
                'UPDATE orders SET 
                    is_confirmed = 1,
                    status = \'processing\',
                    payment_status = ?,
                    paid_amount = ?,
                    payment_reference = ?,
                    payment_receipt = COALESCE(NULLIF(?, \'\'), payment_receipt),
                    ocr_status = \'matched\',
                    confirmed_at = NOW(),
                    bot_step = \'verified\'
                 WHERE id = ?'
            );
            $updOrd->execute([
                $payStatus,
                $paidVal,
                $matchedTx['reference_id'],
                $receiptFilename,
                $orderId
            ]);

            // Update Bank Transaction
            $updTx = $pdo->prepare('UPDATE bank_transactions SET status = \'matched\', matched_order_id = ?, matched_at = NOW(), ocr_matched_at = NOW() WHERE id = ?');
            $updTx->execute([$orderId, $matchedTx['id']]);

            // Send WhatsApp Delivery & Tracking Notification
            try {
                $orderNumber = $order['order_number'] ?? "MED-{$orderId}";
                $custName = $order['customer_name'] ?? 'عميلنا العزيز';
                $trackingUrl = "https://zeinperfumes.com/track_order.php?order={$orderNumber}";

                $msg = 
"🎉 *تم تأكيد واستلام تحويلك بنجاح يا أ/ {$custName}!* 🌸\n\n"
. "📦 رقم الطلب: *{$orderNumber}*\n"
. "💰 المبلغ المعتمد: *" . number_format($paidVal, 2) . " ج.م*\n"
. "🔢 رقم العملية البنكية: `{$matchedTx['reference_id']}`\n"
. "─────────────────────\n"
. "✨ *حالة الطلب:* 🧴 *قيد التجهيز والتغليف الفاخر*\n"
. "تم حجز عطورك بنجاح وجاري تجهيز الشحنة لتسليمها لشركة الشحن.\n\n"
. "🔗 *يمكنك متابعة وتتبع شحنتك لحظة بلحظة عبر الرابط التالي:*\n"
. "{$trackingUrl}\n\n"
. "شكراً لاختيارك *زين للعطور*! 👑";

                if (function_exists('sendTextMessage') && !empty($order['customer_phone'])) {
                    sendTextMessage($order['customer_phone'], $msg);
                }
            } catch (\Throwable $e) {
                error_log('Error sending WA confirmation: ' . $e->getMessage());
            }

            return [
                'matched' => true,
                'status' => 'verified',
                'payment_status' => $payStatus,
                'paid_amount' => $paidVal,
                'reference_id' => $matchedTx['reference_id'],
                'message' => 'تم مطابقة الإيصال مع رسالة البنك وتأكيد الطلب آلياً بنجاح!'
            ];
        }

        // ── MATCH PENDING (Awaiting SMS from phone listener) ──
        $updPending = $pdo->prepare(
            'UPDATE orders SET 
                payment_receipt = COALESCE(NULLIF(?, \'\'), payment_receipt),
                payment_reference = COALESCE(NULLIF(?, \'\'), payment_reference),
                ocr_status = \'scanned\',
                payment_status = \'pending_verification\',
                bot_step = \'receipt_uploaded\'
             WHERE id = ?'
        );
        $updPending->execute([
            $receiptFilename,
            $refId,
            $orderId
        ]);

        return [
            'matched' => false,
            'status' => 'pending_sync',
            'extracted_ref' => $refId,
            'extracted_amount' => $amount,
            'message' => 'تم فحص وقراءة الإيصال بنجاح وجاري المطابقة مع إشعار البنك فور وصوله.'
        ];
    }
}
