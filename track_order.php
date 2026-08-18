<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = current_lang() === 'ar' ? 'تتبع حالة طلبك' : 'Track Your Order';
$isTrackPage = true;

$pdo = medal_pdo();
$orderNumber = isset($_GET['order_number']) ? trim((string) $_GET['order_number']) : '';
$phone = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';

$order = null;
$orderItems = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($orderNumber !== '' || $phone !== '')) {
    if ($orderNumber === '' || $phone === '') {
        $error = current_lang() === 'ar' ? 'يرجى إدخال رقم الطلب ورقم الهاتف معاً.' : 'Please enter both the order number and phone number.';
    } else {
        if ($pdo) {
            try {
                // Find order matching both order number and phone
                $st = $pdo->prepare("SELECT * FROM orders WHERE (order_number = ? OR id = ?) AND customer_phone LIKE ?");
                // Support exact match or matching trailing digits
                $phoneSearch = '%' . ltrim($phone, '0+');
                $st->execute([$orderNumber, (int)$orderNumber, $phoneSearch]);
                $order = $st->fetch();

                if ($order) {
                    $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
                    $it->execute([$order['id']]);
                    $orderItems = $it->fetchAll();
                } else {
                    $error = current_lang() === 'ar' ? 'عذراً، لم نتمكن من العثور على طلب مطابق للبيانات المدخلة.' : 'Sorry, we could not find an order matching the provided details.';
                }
            } catch (Throwable $e) {
                $error = current_lang() === 'ar' ? 'حدث خطأ أثناء البحث، يرجى المحاولة لاحقاً.' : 'An error occurred during search. Please try again later.';
            }
        } else {
            $error = current_lang() === 'ar' ? 'فشل الاتصال بقاعدة البيانات.' : 'Database connection failed.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: clamp(2rem, 5vw, 120px); padding-bottom: 100px; max-width: 800px; font-family: 'Tajawal', sans-serif;">
    
    <header style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.2rem; font-weight: 700; color: #111; margin-bottom: 0.5rem;">
            <?= current_lang() === 'ar' ? 'تتبع حالة طلبك 📦' : 'Track Your Order 📦' ?>
        </h1>
        <p style="color: #777; font-size: 1rem;">
            <?= current_lang() === 'ar' ? 'أدخل تفاصيل طلبك لمعرفة حالة التجهيز والشحن الحالية فوراً' : 'Enter your order details to check the live preparation and shipping status' ?>
        </p>
    </header>

    <!-- Search Form -->
    <div style="background: #fff; padding: 2.5rem; border-radius: 24px; border: 1px solid #f0f0f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 3rem;">
        <form method="GET" action="" class="track-form">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: #333;">
                    <?= current_lang() === 'ar' ? 'رقم الطلب (مثال: ZN-1234)' : 'Order Number (e.g. ZN-1234)' ?>
                </label>
                <input type="text" name="order_number" required value="<?= esc($orderNumber) ?>" placeholder="ZN-XXXX"
                       style="width: 100%; padding: 0.9rem 1.2rem; border: 1px solid #e0e0e0; border-radius: 12px; font-size: 1rem; color: #222; outline: none; transition: border-color 0.2s;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: #333;">
                    <?= current_lang() === 'ar' ? 'رقم الهاتف المسجل' : 'Registered Phone Number' ?>
                </label>
                <input type="tel" name="phone" required value="<?= esc($phone) ?>" placeholder="01XXXXXXXXX"
                       style="width: 100%; padding: 0.9rem 1.2rem; border: 1px solid #e0e0e0; border-radius: 12px; font-size: 1rem; color: #222; outline: none; transition: border-color 0.2s;">
            </div>
            <button type="submit" style="background: #111; color: #fff; border: none; padding: 0.9rem 2rem; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.2s ease;">
                <?= current_lang() === 'ar' ? 'تتبع الآن' : 'Track Now' ?>
            </button>
        </form>
        
        <?php if ($error !== ''): ?>
            <div style="background: #fff5f5; border-right: 4px solid #ff4757; color: #d63031; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; font-weight: 500;">
                ⚠️ <?= esc($error) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Tracking Result -->
    <?php if ($order): ?>
        <div style="background: #fff; padding: 2.5rem; border-radius: 24px; border: 1px solid #f0f0f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); animation: fadeInUp 0.4s ease-out;">
            
            <!-- Order Status Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="font-size: 0.85rem; text-transform: uppercase; color: #888; font-weight: 700; letter-spacing: 1px;"><?= current_lang() === 'ar' ? 'طلب رقم' : 'Order Reference' ?></span>
                    <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111;"><?= esc($order['order_number']) ?></h2>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.85rem; text-transform: uppercase; color: #888; font-weight: 700; letter-spacing: 1px;"><?= current_lang() === 'ar' ? 'تاريخ الطلب' : 'Placed On' ?></span>
                    <div style="font-weight: 600; color: #333; font-size: 1rem;"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></div>
                </div>
            </div>

            <!-- Stepper Timeline -->
            <div class="tracking-stepper" style="margin-bottom: 3.5rem; padding: 1rem 0;">
                <?php
                $statusSteps = [
                    'pending' => ['label_ar' => 'تم استلام الطلب', 'label_en' => 'Received', 'desc_ar' => 'تلقينا طلبك وسنقوم بمراجعته قريباً', 'desc_en' => 'We received your order and will review it soon'],
                    'processing' => ['label_ar' => 'قيد التجهيز', 'label_en' => 'Preparing', 'desc_ar' => 'يتم تركيب وتعبئة عطورك بعناية فائقة', 'desc_en' => 'Your perfumes are being carefully bottled and packed'],
                    'shipped' => ['label_ar' => 'تم الشحن', 'label_en' => 'Shipped', 'desc_ar' => 'طلبك مع مندوب الشحن وسيصلك قريباً', 'desc_en' => 'Your order is on its way with the delivery representative'],
                    'delivered' => ['label_ar' => 'تم التوصيل', 'label_en' => 'Delivered', 'desc_ar' => 'نتمنى أن تنال عطورنا رضاك التام!', 'desc_en' => 'We hope you love your new Zain fragrances!'],
                ];

                $currentStatus = $order['status'];
                $isCancelled = ($currentStatus === 'cancelled');

                $states = ['pending', 'processing', 'shipped', 'delivered'];
                $currentIdx = array_search($currentStatus, $states);
                if ($currentIdx === false) {
                    $currentIdx = 0; // fallback if pending
                }
                ?>

                <?php if ($isCancelled): ?>
                    <div style="background: #fff5f5; border: 1px dashed #d63031; padding: 1.5rem; border-radius: 16px; text-align: center; color: #d63031;">
                        <h3 style="margin: 0 0 0.5rem 0; font-weight: 700;"><?= current_lang() === 'ar' ? 'تم إلغاء الطلب 🛑' : 'Order Cancelled 🛑' ?></h3>
                        <p style="margin: 0; font-size: 0.95rem; color: #888;"><?= current_lang() === 'ar' ? 'نأسف لإبلاغك بأنه تم إلغاء هذا الطلب. إذا كان لديك أي استفسار يرجى التواصل مع الدعم.' : 'We regret to inform you that this order has been cancelled. Please contact support for any inquiries.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="stepper-track-line"></div>
                    <div class="stepper-steps">
                        <?php foreach ($states as $idx => $stepKey): 
                            $step = $statusSteps[$stepKey];
                            $stepName = current_lang() === 'ar' ? $step['label_ar'] : $step['label_en'];
                            $stepDesc = current_lang() === 'ar' ? $step['desc_ar'] : $step['desc_en'];
                            
                            $isDone = ($idx <= $currentIdx);
                            $isCurrent = ($idx === $currentIdx);
                            
                            $stepClass = $isDone ? 'is-done' : '';
                            if ($isCurrent) $stepClass .= ' is-current';
                        ?>
                            <div class="stepper-step <?= $stepClass ?>">
                                <div class="stepper-icon">
                                    <?php if ($isDone && !$isCurrent): ?>
                                        ✓
                                    <?php else: ?>
                                        <?= $idx + 1 ?>
                                    <?php endif; ?>
                                </div>
                                <div class="stepper-content">
                                    <h4 class="stepper-title"><?= esc($stepName) ?></h4>
                                    <p class="stepper-desc"><?= esc($stepDesc) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Information Details -->
            <div style="background: #fafafa; padding: 2rem; border-radius: 16px; border: 1px solid #f0f0f0;">
                <h3 style="margin-top: 0; font-size: 1.1rem; font-weight: 700; color: #222; margin-bottom: 1.5rem; border-bottom: 1px solid #eaeaea; padding-bottom: 0.8rem;">
                    <?= current_lang() === 'ar' ? 'تفاصيل الشحن والمنتجات' : 'Shipping & Product Summary' ?>
                </h3>
                
                <div style="margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.6;">
                    <strong><?= current_lang() === 'ar' ? 'المستلم:' : 'Recipient:' ?></strong> <?= esc($order['customer_name']) ?><br>
                    <strong><?= current_lang() === 'ar' ? 'عنوان الشحن:' : 'Shipping Address:' ?></strong> <?= esc($order['shipping_address']) ?>, <?= esc($order['city']) ?>
                </div>

                <div style="border-top: 1px solid #eaeaea; padding-top: 1rem;">
                    <?php foreach ($orderItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; font-size: 0.95rem;">
                            <div>
                                <span style="font-weight: 600; color: #222;"><?= esc($item['product_name_snapshot']) ?></span>
                                <?php if ($item['variant_label_snapshot']): ?>
                                    <span style="font-size: 0.8rem; background: #eee; padding: 0.15rem 0.4rem; border-radius: 4px; margin-inline-start: 0.5rem; color: #555;"><?= esc($item['variant_label_snapshot']) ?></span>
                                <?php endif; ?>
                                <span style="color: #888; font-size: 0.85rem;"> &times; <?= (int)$item['qty'] ?></span>
                            </div>
                            <div style="font-weight: 700; color: #111;">
                                <?= format_price($item['line_total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px solid #eaeaea; padding-top: 1rem; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 700;">
                    <span><?= current_lang() === 'ar' ? 'الإجمالي الكلي:' : 'Total Amount:' ?></span>
                    <span style="color: #c5a059; font-size: 1.2rem;"><?= format_price($order['total']) ?></span>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.tracking-stepper {
    position: relative;
}
.stepper-track-line {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 2px;
    background: #e0e0e0;
    z-index: 1;
}
html[dir="rtl"] .stepper-track-line {
    left: auto;
    right: 20px;
}
.stepper-steps {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
.stepper-step {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}
.stepper-icon {
    width: 42px;
    height: 42px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #999;
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.stepper-step.is-done .stepper-icon {
    background: #c5a059;
    border-color: #c5a059;
    color: #fff;
}
.stepper-step.is-current .stepper-icon {
    background: #111;
    border-color: #111;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(0,0,0,0.1);
}
.stepper-content {
    flex-grow: 1;
}
.stepper-title {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #888;
    transition: all 0.3s ease;
}
.stepper-step.is-done .stepper-title {
    color: #111;
}
.stepper-step.is-current .stepper-title {
    color: #c5a059;
}
.stepper-desc {
    margin: 0;
    font-size: 0.85rem;
    color: #bbb;
    line-height: 1.5;
}
.stepper-step.is-done .stepper-desc {
    color: #666;
}
@media (min-width: 600px) {
    .stepper-track-line {
        left: 0;
        right: 0;
        top: 20px;
        bottom: auto;
        height: 2px;
        width: 100%;
    }
    .stepper-steps {
        flex-direction: row;
        justify-content: space-between;
        gap: 1rem;
    }
    .stepper-step {
        flex-direction: column;
        align-items: center;
        text-align: center;
        flex: 1;
    }
    .stepper-icon {
        margin-bottom: 0.8rem;
    }
}

/* Track Order: mobile form fix */
.track-form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1.2rem;
    align-items: end;
}
@media (max-width: 640px) {
    .track-form {
        grid-template-columns: 1fr !important;
    }
    .track-form button[type="submit"] {
        width: 100% !important;
    }
    /* Padding fixes for mobile */
    .container[style*="padding-top"] {
        padding-top: 1.5rem !important;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
