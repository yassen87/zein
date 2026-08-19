<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$orderItems = [];
$pdo = medal_pdo();

if ($pdo && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();

    if ($order) {
        $itSt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $itSt->execute([$id]);
        $orderItems = $itSt->fetchAll();
    }
}

if (!$order) {
    header('Location: ' . url('index.php'));
    exit;
}

$pageTitle = current_lang() === 'ar' ? 'تم استلام طلبك بنجاح' : 'Order Received Successfully';
$isArabic = current_lang() === 'ar';
$orderNumber = $order['order_number'] ?? ('#' . $order['id']);
$total = (float)($order['total'] ?? 0);
$subtotal = (float)($order['subtotal'] ?? $total);
$shippingCost = (float)($order['shipping_cost'] ?? 0);
$discountAmount = (float)($order['discount_amount'] ?? 0);
$custName = (string)($order['customer_name'] ?? 'عميلنا العزيز');

$fallbackMsg = "🌸 أهلاً بك يا أ/ {$custName} في متجر زين للعطور 🌸\n\n";
$fallbackMsg .= "📦 تم تسجيل طلبك بنجاح برقم: *{$orderNumber}*\n";
$fallbackMsg .= "💰 إجمالي المبلغ المطلوب: *" . number_format($total, 2) . " ج.م*\n\n";
$fallbackMsg .= "يرجى الرد برقم الخيار لتأكيد طلبك:\n";
$fallbackMsg .= "1️⃣ - *تأكيد الطلب واختيار نظام الدفع*\n";
$fallbackMsg .= "2️⃣ - *إلغاء الطلب*\n";
$fallbackMsg .= "3️⃣ - *تعديل الطلب من على الموقع*\n";

$waUrl = contact_whatsapp_url(1) . '?text=' . urlencode($fallbackMsg);

require __DIR__ . '/includes/header.php';
?>

<style>
.success-page-wrap {
    padding-top: clamp(2rem, 5vw, 90px);
    padding-bottom: 90px;
    background: radial-gradient(circle at top, rgba(212, 175, 55, 0.06) 0%, transparent 65%);
    font-family: 'Tajawal', sans-serif;
}
.order-card {
    background: #ffffff;
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 24px;
    box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    margin-bottom: 2rem;
}
.order-card-header {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #fff;
    padding: 2.75rem 2rem;
    text-align: center;
    position: relative;
}
.gold-badge {
    background: linear-gradient(135deg, #d4af37 0%, #aa8420 100%);
    color: #111827;
    font-weight: 800;
    padding: 0.4rem 1.25rem;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}
.stepper-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin: 2.25rem 0;
}
.stepper-wrap::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 12%;
    right: 12%;
    height: 3px;
    background: #e5e7eb;
    z-index: 1;
}
.stepper-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}
.stepper-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: 700;
    font-size: 1rem;
    color: #6b7280;
    transition: all 0.3s;
}
.stepper-step.active .stepper-icon {
    background: #d4af37;
    border-color: #d4af37;
    color: #111827;
    box-shadow: 0 0 18px rgba(212, 175, 55, 0.4);
}
.stepper-step.completed .stepper-icon {
    background: #10b981;
    border-color: #10b981;
    color: #ffffff;
}
.stepper-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #374151;
}
.wa-callout-box {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    color: #fff;
    border-radius: 20px;
    padding: 1.75rem 2rem;
    margin: 2rem 0;
    box-shadow: 0 10px 25px -5px rgba(6, 78, 59, 0.3);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.btn-wa-action {
    background: #25d366;
    color: #ffffff;
    font-weight: 800;
    font-size: 1rem;
    padding: 0.85rem 1.75rem;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 6px 18px rgba(37, 211, 102, 0.3);
    transition: all 0.2s;
    border: none;
}
.btn-wa-action:hover {
    background: #20ba5a;
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(37, 211, 102, 0.4);
    color: #ffffff;
}
.btn-primary-action {
    background: #111827;
    color: #ffffff;
    font-weight: 700;
    padding: 0.85rem 1.75rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-primary-action:hover {
    background: #d4af37;
    color: #111827;
}
</style>

<div class="success-page-wrap">
    <div class="container narrow" style="max-width: 800px;">
        
        <!-- Main Order Card -->
        <div class="order-card">
            
            <!-- Header Banner -->
            <div class="order-card-header">
                <div style="width: 68px; height: 68px; background: rgba(212, 175, 55, 0.2); border: 2px solid #d4af37; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 2rem; color: #d4af37;">
                    ✓
                </div>
                
                <div class="gold-badge" style="margin-bottom: 0.75rem;">
                    <?= $isArabic ? 'تم تسجيل الطلب بنجاح' : 'Order Placed Successfully' ?>
                </div>

                <h1 style="font-size: 2.1rem; font-weight: 800; margin: 0 0 0.5rem; color: #ffffff;">
                    <?= $isArabic ? 'شكراً لك، ' . esc((string)$order['customer_name']) : 'Thank you, ' . esc((string)$order['customer_name']) ?>
                </h1>

                <p style="color: #9ca3af; font-size: 1rem; margin: 0 auto; max-width: 480px;">
                    <?= $isArabic ? 'تم استلام طلبك وجاري إرسال تفاصيل التأكيد إلى رقم هاتفك.' : 'Your order has been received and confirmation details are on their way to your phone.' ?>
                </p>

                <div style="margin-top: 1.5rem; display: inline-flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.06); padding: 0.6rem 1.5rem; border-radius: 14px; border: 1px solid rgba(255,255,255,0.12);">
                    <span style="color: #9ca3af; font-size: 0.9rem;"><?= $isArabic ? 'رقم الطلب:' : 'Order Ref:' ?></span>
                    <strong style="font-size: 1.35rem; color: #d4af37; letter-spacing: 1px;"><?= esc($orderNumber) ?></strong>
                </div>
            </div>

            <!-- Content Area -->
            <div style="padding: 2.25rem 2rem;">
                
                <!-- Stepper -->
                <div class="stepper-wrap">
                    <div class="stepper-step completed">
                        <div class="stepper-icon">✓</div>
                        <div class="stepper-label"><?= $isArabic ? 'تسجيل الطلب' : 'Placed' ?></div>
                    </div>
                    <div class="stepper-step active">
                        <div class="stepper-icon">2</div>
                        <div class="stepper-label"><?= $isArabic ? 'تأكيد الطلب' : 'Confirmation' ?></div>
                    </div>
                    <div class="stepper-step">
                        <div class="stepper-icon">3</div>
                        <div class="stepper-label"><?= $isArabic ? 'تجهيز الشحنة' : 'Packaging' ?></div>
                    </div>
                    <div class="stepper-step">
                        <div class="stepper-icon">4</div>
                        <div class="stepper-label"><?= $isArabic ? 'الشحن والتوصيل' : 'Delivery' ?></div>
                    </div>
                </div>

                <!-- WhatsApp Notification Single-Platform Box -->
                <div class="wa-callout-box">
                    <div style="font-size: 2.8rem; line-height: 1;">📲</div>
                    <div style="flex: 1; min-width: 250px;">
                        <h3 style="margin: 0 0 0.4rem; font-size: 1.2rem; font-weight: 800; color: #6ee7b7;">
                            <?= $isArabic ? 'متابعة وتأكيد طلبك عبر الواتساب' : 'Follow up & Confirm on WhatsApp' ?>
                        </h3>
                        <p style="margin: 0; font-size: 0.92rem; line-height: 1.6; color: #d1fae5;">
                            <?= $isArabic 
                                ? 'وصلتك الآن رسالة تلقائية على الواتساب تحتوي على خيارات الطلب وتفاصيل الدفع. يمكنك الرد والتأكيد مباشرة من محادثة الواتساب.' 
                                : 'You received an automated WhatsApp message with your order options and payment details.' ?>
                        </p>
                    </div>
                    <div>
                        <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-wa-action">
                            <span>💬 <?= $isArabic ? 'فتح محادثة الواتساب' : 'Open WhatsApp Chat' ?></span>
                        </a>
                    </div>
                </div>

                <!-- Order Items Summary Table -->
                <div style="margin-top: 2rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        🛒 <span><?= $isArabic ? 'تفاصيل المنتجات المطلوبة:' : 'Order Items:' ?></span>
                    </h3>
                    
                    <div style="border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; background: #fafafa;">
                        <?php foreach ($orderItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; background: #ffffff;">
                                <div>
                                    <strong style="color: #111827; font-size: 0.95rem; display: block;"><?= esc((string)$item['product_name_snapshot']) ?></strong>
                                    <?php if (!empty($item['variant_label_snapshot'])): ?>
                                        <span style="color: #6b7280; font-size: 0.82rem;"><?= esc((string)$item['variant_label_snapshot']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: left;" dir="ltr">
                                    <span style="color: #6b7280; font-size: 0.85rem; margin-right: 0.75rem;">x<?= (int)$item['qty'] ?></span>
                                    <strong style="color: #111827; font-size: 0.95rem;"><?= number_format((float)$item['line_total'], 2) ?> <?= esc(t('currency')) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Financial Summary -->
                        <div style="padding: 1rem 1.25rem; background: #fafafa; font-size: 0.9rem; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                                <span><?= $isArabic ? 'المجموع الفرعي:' : 'Subtotal:' ?></span>
                                <span><?= number_format($subtotal, 2) ?> <?= esc(t('currency')) ?></span>
                            </div>
                            <?php if ($discountAmount > 0): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem; color: #10b981;">
                                    <span><?= $isArabic ? 'الخصم:' : 'Discount:' ?></span>
                                    <span>-<?= number_format($discountAmount, 2) ?> <?= esc(t('currency')) ?></span>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span><?= $isArabic ? 'تكلفة الشحن:' : 'Shipping Cost:' ?></span>
                                <span><?= $shippingCost > 0 ? number_format($shippingCost, 2) . ' ' . esc(t('currency')) : ($isArabic ? 'مجاني' : 'Free') ?></span>
                            </div>
                        </div>
                        
                        <!-- Grand Total -->
                        <div style="background: #fdfaf3; padding: 1.15rem 1.25rem; display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 1.15rem; color: #111827; border-top: 1px solid rgba(212,175,55,0.3);">
                            <span><?= $isArabic ? 'المجموع النهائي:' : 'Grand Total:' ?></span>
                            <span style="color: #d4af37; font-size: 1.35rem;"><?= number_format($total, 2) ?> <?= esc(t('currency')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Footer Navigation Actions -->
                <div style="margin-top: 2.5rem; text-align: center; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?= esc(url('track_order.php?order_number=' . urlencode($orderNumber) . '&phone=' . urlencode((string)$order['customer_phone']))) ?>" class="btn-primary-action">
                        🔍 <?= $isArabic ? 'تتبع حالة الشحنة' : 'Track Order Status' ?>
                    </a>
                    <a href="<?= esc(url('index.php')) ?>" class="secondary-btn" style="padding: 0.85rem 1.75rem; border-radius: 12px; background: #ffffff; color: #111827; border: 1px solid #d1d5db;">
                        🏠 <?= $isArabic ? 'العودة للصفحة الرئيسية' : 'Back to Home' ?>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
