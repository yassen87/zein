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

$fallbackMsg = "🌸 *أهلاً بك يا أ/ {$custName} في متجر زين للعطور* 🌸\n\n";
$fallbackMsg .= "📦 تم استلام طلبك بنجاح برقم: *{$orderNumber}*\n";
if ($shippingCost > 0) {
    $fallbackMsg .= "🚚 مصاريف الشحن: *" . number_format($shippingCost, 2) . " ج.م*\n";
}
$fallbackMsg .= "💰 إجمالي المبلغ: *" . number_format($total, 2) . " ج.م*\n";
$fallbackMsg .= "─────────────────────\n";
$fallbackMsg .= "يرجى اختيار الإجراء المطلوب بالرد برقم الخيار:\n\n";
$fallbackMsg .= "1️⃣ - *تأكيد الطلب ونظام الدفع* 💳\n";
$fallbackMsg .= "2️⃣ - *إلغاء الطلب* ❌\n";
$fallbackMsg .= "3️⃣ - *تعديل بيانات الطلب* ✏️\n\n";
$fallbackMsg .= "_(يرجى الرد برقم 1 أو 2 أو 3 للمتابعة)_";

$waUrl = contact_whatsapp_url(1) . '?text=' . urlencode($fallbackMsg);

require __DIR__ . '/includes/header.php';
?>

<style>
.success-page-wrap {
    padding-top: clamp(2rem, 4vw, 60px);
    padding-bottom: 80px;
    background: radial-gradient(circle at top, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
    font-family: 'Tajawal', sans-serif;
}
.order-card {
    background: #ffffff;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 2rem;
}
.order-card-header {
    background: linear-gradient(135deg, #111827 0%, #1e293b 100%);
    color: #fff;
    padding: 2.5rem 2rem;
    text-align: center;
    position: relative;
    border-bottom: 3px solid #d4af37;
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
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}
.wa-hero-box {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    border: 2px solid #10b981;
    border-radius: 20px;
    padding: 1.75rem;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
    margin-bottom: 2rem;
}
.btn-wa-action {
    background: #25d366;
    color: #ffffff !important;
    font-weight: 800;
    font-size: 1.1rem;
    padding: 1rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
    transition: all 0.25s ease;
    border: 2px solid #ffffff;
}
.btn-wa-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(37, 211, 102, 0.55);
    background: #22c55e;
}
.product-thumb-img {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    object-fit: cover;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.stepper-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin: 1.5rem 0 2rem;
}
.stepper-wrap::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 12%;
    right: 12%;
    height: 3px;
    background: #e2e8f0;
    z-index: 1;
}
.stepper-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}
.stepper-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f1f5f9;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.4rem;
    font-weight: 700;
    font-size: 0.95rem;
}
.stepper-step.completed .stepper-icon {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}
.stepper-step.active .stepper-icon {
    background: #d4af37;
    border-color: #d4af37;
    color: #111827;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.25);
}
.stepper-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
}
.stepper-step.active .stepper-label {
    color: #d4af37;
}
.btn-primary-action {
    background: #111827;
    color: #d4af37;
    border: 1px solid #d4af37;
    font-weight: 700;
    padding: 0.85rem 1.75rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-primary-action:hover {
    background: #d4af37;
    color: #111827;
}
</style>

<div class="success-page-wrap">
    <div class="container narrow" style="max-width: 820px;">
        
        <!-- Main Order Card -->
        <div class="order-card">
            
            <!-- Header Banner -->
            <div class="order-card-header">
                <div style="width: 64px; height: 64px; background: rgba(212, 175, 55, 0.2); border: 2px solid #d4af37; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.8rem; color: #d4af37;">
                    ✓
                </div>
                
                <div class="gold-badge" style="margin-bottom: 0.75rem;">
                    <?= $isArabic ? 'تم استلام وتجهيز طلبك بنجاح' : 'Order Received Successfully' ?>
                </div>

                <h1 style="font-size: 2rem; font-weight: 800; margin: 0 0 0.5rem; color: #ffffff;">
                    <?= $isArabic ? 'شكراً لك يا أ/ ' . esc($custName) : 'Thank you, ' . esc($custName) ?>
                </h1>

                <div style="margin-top: 1.25rem; display: inline-flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.08); padding: 0.6rem 1.5rem; border-radius: 14px; border: 1px solid rgba(255,255,255,0.15);">
                    <span style="color: #cbd5e1; font-size: 0.95rem;"><?= $isArabic ? 'رقم طلبك:' : 'Order Ref:' ?></span>
                    <strong style="font-size: 1.4rem; color: #d4af37; letter-spacing: 1px;"><?= esc($orderNumber) ?></strong>
                </div>
            </div>

            <!-- Content Area -->
            <div style="padding: 2rem;">
                
                <!-- WhatsApp Top Priority Hero Box -->
                <div class="wa-hero-box">
                    <div style="flex: 1; min-width: 260px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.8rem;">📲</span>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #a7f3d0;">
                                <?= $isArabic ? 'تأكيد طلبك فـوراً عبر الواتساب' : 'Confirm Order Instantly via WhatsApp' ?>
                            </h3>
                        </div>
                        <p style="margin: 0; font-size: 0.95rem; line-height: 1.6; color: #ecfdf5;">
                            <?= $isArabic 
                                ? 'اضغط على الزر لتأكيد طلبك ومعرفة تفاصيل الشحن والتحويل مع خدمة العملاء.' 
                                : 'Click the button below to confirm your order and get delivery details directly.' ?>
                        </p>
                    </div>
                    <div>
                        <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-wa-action">
                            <span>💬 <?= $isArabic ? 'فتح محادثة الواتساب الآن' : 'Open WhatsApp Chat' ?></span>
                        </a>
                    </div>
                </div>

                <!-- Stepper -->
                <div class="stepper-wrap">
                    <div class="stepper-step completed">
                        <div class="stepper-icon">✓</div>
                        <div class="stepper-label"><?= $isArabic ? 'تسجيل الطلب' : 'Placed' ?></div>
                    </div>
                    <div class="stepper-step active">
                        <div class="stepper-icon">2</div>
                        <div class="stepper-label"><?= $isArabic ? 'تأكيد الدفع' : 'Confirmation' ?></div>
                    </div>
                    <div class="stepper-step">
                        <div class="stepper-icon">3</div>
                        <div class="stepper-label"><?= $isArabic ? 'تجهيز العطور' : 'Packaging' ?></div>
                    </div>
                    <div class="stepper-step">
                        <div class="stepper-icon">4</div>
                        <div class="stepper-label"><?= $isArabic ? 'الشحن والتوصيل' : 'Delivery' ?></div>
                    </div>
                </div>

                <!-- Order Items Summary Table with Thumbnails -->
                <div style="margin-top: 1.5rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        🛒 <span><?= $isArabic ? 'العطور والمنتجات التي قمت بطلبها:' : 'Ordered Items:' ?></span>
                    </h3>
                    
                    <div style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background: #fafafa;">
                        <?php foreach ($orderItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; background: #ffffff; gap: 1rem;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(212,175,55,0.12); border: 1px solid rgba(212,175,55,0.25); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">🧴</div>
                                    <div>
                                        <strong style="color: #0f172a; font-size: 1rem; display: block;"><?= esc((string)$item['product_name_snapshot']) ?></strong>
                                        <?php if (!empty($item['variant_label_snapshot'])): ?>
                                            <span style="color: #64748b; font-size: 0.85rem; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 3px;"><?= esc((string)$item['variant_label_snapshot']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align: left; white-space: nowrap;" dir="ltr">
                                    <span style="color: #64748b; font-size: 0.9rem; margin-right: 0.75rem;">x<?= (int)$item['qty'] ?></span>
                                    <strong style="color: #0f172a; font-size: 1.05rem;"><?= number_format((float)$item['line_total'], 2) ?> <?= esc(t('currency')) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Financial Summary -->
                        <div style="padding: 1rem 1.25rem; background: #f8fafc; font-size: 0.92rem; color: #475569; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                                <span><?= $isArabic ? 'المجموع الفرعي:' : 'Subtotal:' ?></span>
                                <span><?= number_format($subtotal, 2) ?> <?= esc(t('currency')) ?></span>
                            </div>
                            <?php if ($discountAmount > 0): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem; color: #10b981; font-weight: 600;">
                                    <span><?= $isArabic ? 'قيمة الخصم:' : 'Discount:' ?></span>
                                    <span>-<?= number_format($discountAmount, 2) ?> <?= esc(t('currency')) ?></span>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span><?= $isArabic ? 'مصاريف الشحن:' : 'Shipping Cost:' ?></span>
                                <span><?= $shippingCost > 0 ? number_format($shippingCost, 2) . ' ' . esc(t('currency')) : ($isArabic ? 'مجاني' : 'Free') ?></span>
                            </div>
                        </div>
                        
                        <!-- Grand Total -->
                        <div style="background: #fefce8; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 1.15rem; color: #0f172a; border-top: 1px solid rgba(212,175,55,0.3);">
                            <span><?= $isArabic ? 'المبلغ الإجمالي المستحق:' : 'Grand Total:' ?></span>
                            <span style="color: #b45309; font-size: 1.45rem;"><?= number_format($total, 2) ?> <?= esc(t('currency')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Footer Navigation Actions -->
                <div style="margin-top: 2rem; text-align: center; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?= esc(url('track_order.php?order_number=' . urlencode($orderNumber) . '&phone=' . urlencode((string)$order['customer_phone']))) ?>" class="btn-primary-action">
                        🔍 <?= $isArabic ? 'تتبع حالة الشحنة' : 'Track Order Status' ?>
                    </a>
                    <a href="<?= esc(url('index.php')) ?>" class="secondary-btn" style="padding: 0.85rem 1.75rem; border-radius: 12px; background: #ffffff; color: #111827; border: 1px solid #d1d5db; text-decoration: none; font-weight: 600;">
                        🏠 <?= $isArabic ? 'العودة للمتجر' : 'Back to Store' ?>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
