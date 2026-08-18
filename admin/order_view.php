<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pdo = medal_pdo();
$id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $id = (int) ($_POST['order_id'] ?? $id);
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && $id > 0) {
        $status = (string) ($_POST['status'] ?? '');
        $notes = trim((string) ($_POST['admin_notes'] ?? ''));
        if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
            $prevStatusSt = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
            $prevStatusSt->execute([$id]);
            $prevStatus = $prevStatusSt->fetchColumn();

            $u = $pdo->prepare('UPDATE orders SET status = ?, delivered_at = CASE WHEN ? = \'delivered\' THEN COALESCE(delivered_at, NOW()) ELSE NULL END, admin_notes = ? WHERE id = ?');
            $u->execute([$status, $status, $notes !== '' ? $notes : null, $id]);

            // If status actually changed, send WhatsApp notification
            if ($prevStatus !== $status) {
                require_once __DIR__ . '/../includes/whatsapp_helper.php';
                send_whatsapp_order_status_notification($id, $status);
            }
        }
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
    if ($action === 'pay_part' && $id > 0) {
        $amount = (float)($_POST['amount_to_pay'] ?? 0.0);
        if ($amount > 0.0) {
            $u = $pdo->prepare('UPDATE orders SET paid_amount = paid_amount + ? WHERE id = ?');
            $u->execute([$amount, $id]);
        }
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
    if ($action === 'waive_remaining' && $id > 0) {
        $st = $pdo->prepare('SELECT total, paid_amount, waived_amount FROM orders WHERE id = ?');
        $st->execute([$id]);
        $ord = $st->fetch();
        if ($ord) {
            $remaining = (float)$ord['total'] - (float)$ord['paid_amount'] - (float)$ord['waived_amount'];
            if ($remaining > 0.0) {
                $u = $pdo->prepare('UPDATE orders SET waived_amount = waived_amount + ? WHERE id = ?');
                $u->execute([$remaining, $id]);
            }
        }
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
    if ($action === 'approve_receipt' && $id > 0) {
        $st = $pdo->prepare('SELECT total, shipping_cost, payment_scope, advance_amount, customer_phone, customer_name, order_number FROM orders WHERE id = ?');
        $st->execute([$id]);
        $ordRow = $st->fetch();
        if ($ordRow) {
            $scope = $ordRow['payment_scope'] ?? 'full';
            $totalVal = (float)$ordRow['total'];
            $shipVal = (float)$ordRow['shipping_cost'];
            $advanceVal = (float)($ordRow['advance_amount'] ?? 0);
            $paidVal = ($scope === 'shipping_only') ? ($advanceVal > 0 ? $advanceVal : $shipVal) : $totalVal;
            $payStatus = ($scope === 'shipping_only') ? 'deposit_paid' : 'verified';

            $u = $pdo->prepare('UPDATE orders SET payment_status = ?, is_confirmed = 1, paid_amount = ?, status = \'processing\', confirmed_at = COALESCE(confirmed_at, NOW()) WHERE id = ?');
            $u->execute([$payStatus, $paidVal, $id]);

            // Notify customer via WhatsApp
            try {
                require_once __DIR__ . '/../includes/whatsapp_helper.php';
                $waPhone = preg_replace('/\D/', '', (string)$ordRow['customer_phone']);
                if (str_starts_with($waPhone, '01') && strlen($waPhone) === 11) $waPhone = '2' . $waPhone;
                $approvalMsg = "🎉 *تم اعتماد وتأكيد دفعتك بنجاح!* 🌸\n\n📦 طلب رقم: *" . $ordRow['order_number'] . "*\n✨ الحالة: *تم التحقق من التحويل وبدء تجهيز وتغليف شحنتك فوراً.*\n\nشكراً لاختيارك *زين للعطور*! سيصلك إشعار عند خروج المندوب للتوصيل. 🚚";
                
                $botUrl = 'http://127.0.0.1:3001/api/test-message';
                $payload = json_encode(['phone' => $waPhone, 'message' => $approvalMsg]);
                $ch = curl_init($botUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $e) {}
        }
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
    if ($action === 'reject_receipt' && $id > 0) {
        $u = $pdo->prepare('UPDATE orders SET payment_receipt = NULL, payment_status = \'unpaid\' WHERE id = ?');
        $u->execute([$id]);
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
    if ($action === 'toggle_confirmed' && $id > 0) {
        $u = $pdo->prepare('UPDATE orders SET is_confirmed = CASE WHEN is_confirmed = 1 THEN 0 ELSE 1 END, confirmed_at = NOW() WHERE id = ?');
        $u->execute([$id]);
        header('Location: ' . admin_url('order_view.php?id=' . $id));
        exit;
    }
}

$pageTitle = t('admin_order_title');
$order = null;
$items = [];
$gifts = [];

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();
    if ($order !== false) {
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $it->execute([$id]);
        $items = $it->fetchAll();
        
        try {
            $st_gifts = $pdo->prepare('
                SELECT oip.*, ip.name_ar, ip.name_en, ip.type 
                FROM order_internal_products oip 
                JOIN internal_products ip ON oip.internal_product_id = ip.id 
                WHERE oip.order_id = ? 
                ORDER BY oip.id ASC
            ');
            $st_gifts->execute([$id]);
            $gifts = $st_gifts->fetchAll();
        } catch (\Exception $e) {
            $gifts = [];
        }
    }
}

if ($order === null || $order === false) {
    http_response_code(404);
    $pageTitle = t('admin_page_not_found');
    require __DIR__ . '/_layout_start.php';
    echo '<p>' . esc(t('admin_order_not_found')) . '</p>';
    require __DIR__ . '/_layout_end.php';
    exit;
}

$pageTitle = t('admin_order_title') . ' ' . (string) $order['order_number'];
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="border-bottom: 1px solid var(--admin-card-border); padding-bottom: 1.5rem; margin-bottom: 2rem;">
    <div>
        <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--admin-heading);">
            الطلب <?= esc((string) $order['order_number']) ?>
        </h1>
        <p class="admin-muted" style="margin: 0.5rem 0 0; font-size: 0.9rem;">
            📅 تم تقديم الطلب في: <strong style="color: var(--admin-gold);"><?= esc((string) $order['created_at']) ?></strong>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <form method="POST" action="<?= esc(admin_url('send_order_email.php')) ?>" style="margin: 0;">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="admin-btn admin-btn--secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                📧 إرسال إيميل تأكيد
            </button>
        </form>
        <a href="order_management.php?id=<?= (int)$id ?>" class="admin-btn admin-btn--primary" style="display: inline-flex; align-items: center; gap: 6px;">
            ✏️ تعديل بنود الطلب
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- LEFT COLUMN: Items, Gifts, Payment details -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Line Items -->
        <div class="admin-card" style="padding: 1.75rem;">
            <h2 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1.25rem; font-weight: 700; color: var(--admin-heading); display: flex; align-items: center; gap: 8px;">
                🛒 المنتجات والبنود
            </h2>
            <div class="admin-table-wrap">
                <table class="admin-table" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الحجم / المتغير</th>
                            <th style="text-align: center;">الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--admin-gold);">
                                    <?= esc((string) $row['product_name_snapshot']) ?>
                                    <span style="font-size: 0.75rem; font-weight: normal; color: var(--admin-text-muted); display: block;">معرّف المنتج: #<?= (int) $row['product_id'] ?></span>
                                </td>
                                <td><?= esc((string) ($row['variant_label_snapshot'] ?? '—')) ?></td>
                                <td style="text-align: center; font-weight: 700;"><?= (int) $row['qty'] ?></td>
                                <td><?= number_format((float) $row['unit_price'], 2) ?> <?= esc(t('currency')) ?></td>
                                <td style="font-weight: 700; color: var(--admin-heading);"><?= number_format((float) $row['line_total'], 2) ?> <?= esc(t('currency')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary Box -->
            <div style="margin-top: 1.5rem; background: rgba(212, 175, 55, 0.03); border: 1px solid var(--admin-card-border); padding: 1.25rem; border-radius: 12px; display: flex; flex-direction: column; gap: 0.75rem; max-width: 350px; margin-inline-start: auto;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--admin-text-muted);">
                    <span>المجموع الفرعي:</span>
                    <span><?= number_format((float) $order['subtotal'], 2) ?> <?= esc(t('currency')) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--admin-text-muted);">
                    <span>تكلفة الشحن:</span>
                    <span>+ <?= number_format((float) $order['shipping_cost'], 2) ?> <?= esc(t('currency')) ?></span>
                </div>
                <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #ef4444;">
                        <span>الخصم (كوبون):</span>
                        <span>- <?= number_format((float) $order['discount_amount'], 2) ?> <?= esc(t('currency')) ?></span>
                    </div>
                <?php endif; ?>
                <hr style="border: 0; border-top: 1px solid var(--admin-card-border); margin: 0.25rem 0;">
                <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; color: var(--admin-gold);">
                    <span>إجمالي الطلب:</span>
                    <span><?= number_format((float) $order['total'], 2) ?> <?= esc(t('currency')) ?></span>
                </div>
            </div>
        </div>

        <!-- Gifts and Internal Products -->
        <?php if (!empty($gifts)): ?>
            <div class="admin-card" style="padding: 1.75rem;">
                <h3 style="margin-top: 0; font-size: 1.15rem; color: var(--admin-gold); font-weight: 700; margin-bottom: 1rem;">
                    🎁 الهدايا والعينات المرفقة
                </h3>
                <div class="admin-table-wrap">
                    <table class="admin-table" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>الهدية / العينة</th>
                                <th>النوع</th>
                                <th style="text-align: center;">الكمية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gifts as $g): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?= esc((string) (current_lang() === 'ar' ? $g['name_ar'] : $g['name_en'])) ?></td>
                                    <td>
                                        <span class="admin-badge" style="background: rgba(212, 175, 55, 0.15); color: var(--admin-gold); text-transform: capitalize; border-radius: 6px; padding: 0.2rem 0.5rem; font-size: 0.8rem; font-weight: 700;">
                                            <?= esc((string) $g['type']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;"><?= (int) $g['quantity'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment and Balance Manager -->
        <?php
        $total = (float)$order['total'];
        $paid = (float)($order['paid_amount'] ?? 0.0);
        $waived = (float)($order['waived_amount'] ?? 0.0);
        $remaining = max(0.0, $total - $paid - $waived);
        ?>
        <div class="admin-card" style="padding: 1.75rem;">
            <h2 style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1.25rem; font-weight: 700; color: var(--admin-heading); display: flex; align-items: center; gap: 8px;">
                💵 الحالة المالية وإدارة المدفوعات
            </h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; background: rgba(212, 175, 55, 0.04); border: 1px solid var(--admin-card-border); padding: 1.25rem; border-radius: 12px;">
                <div style="text-align: center;">
                    <span style="font-size: 0.82rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">المجموع الكلي</span>
                    <strong style="font-size: 1.15rem; color: var(--admin-heading);"><?= number_format($total, 2) ?> <?= esc(t('currency')) ?></strong>
                </div>
                <div style="text-align: center; border-inline-start: 1px solid var(--admin-card-border);">
                    <span style="font-size: 0.82rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">المدفوع</span>
                    <strong style="font-size: 1.15rem; color: #10b981;"><?= number_format($paid, 2) ?> <?= esc(t('currency')) ?></strong>
                </div>
                <div style="text-align: center; border-inline-start: 1px solid var(--admin-card-border);">
                    <span style="font-size: 0.82rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">ممسوح (خصم إضافي)</span>
                    <strong style="font-size: 1.15rem; color: #8b5cf6;"><?= number_format($waived, 2) ?> <?= esc(t('currency')) ?></strong>
                </div>
                <div style="text-align: center; border-inline-start: 1px solid var(--admin-card-border);">
                    <span style="font-size: 0.82rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.25rem;">المتبقي المطلوب</span>
                    <strong style="font-size: 1.15rem; color: <?= $remaining > 0 ? '#ef4444' : '#10b981' ?>;"><?= number_format($remaining, 2) ?> <?= esc(t('currency')) ?></strong>
                </div>
            </div>

            <?php if ($remaining > 0): ?>
                <div style="display: flex; gap: 1.5rem; align-items: flex-end; flex-wrap: wrap; border-top: 1px solid var(--admin-card-border); padding-top: 1.25rem;">
                    <!-- Pay part form -->
                    <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" style="display: flex; gap: 0.5rem; align-items: flex-end; margin: 0;">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                        <input type="hidden" name="action" value="pay_part">
                        <div>
                            <label for="amount_to_pay" style="font-size: 0.82rem; display: block; margin-bottom: 0.35rem; font-weight: 600;">تسجيل دفعة جديدة</label>
                            <input type="number" step="0.01" name="amount_to_pay" id="amount_to_pay" max="<?= $remaining ?>" value="<?= $remaining ?>" required class="admin-input" style="width: 130px; padding: 0.55rem;">
                        </div>
                        <button type="submit" class="admin-btn admin-btn--primary" style="background: #10b981; border-color: #10b981; color: white;">تسجيل دفعة</button>
                    </form>

                    <!-- Waive remaining form -->
                    <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" style="margin: 0;" onsubmit="return confirm('هل أنت متأكد من مسح وإسقاط باقي المبلغ المستحق بالكامل؟');">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                        <input type="hidden" name="action" value="waive_remaining">
                        <button type="submit" class="admin-btn admin-btn--secondary" style="background: #8b5cf6; border-color: #8b5cf6; color: white;">💸 مسح باقي الحساب</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1rem; border-radius: 8px; color: #065f46; font-weight: 700;">
                    ✅ الحساب مسوّى بالكامل. لا يوجد مستحقات متبقية على هذا الطلب.
                </div>
            <?php endif; ?>
            
            <!-- Reset payments -->
            <?php if ($paid > 0 || $waived > 0): ?>
                <div style="margin-top: 1.25rem; text-align: inline-end;">
                    <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm('هل تريد إعادة تعيين المدفوعات والخصومات لصفر والبدء من جديد؟');">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                        <input type="hidden" name="action" value="reset_payments">
                        <button type="submit" style="background: none; border: none; color: var(--admin-text-muted); text-decoration: underline; font-size: 0.8rem; cursor: pointer;">
                            🔄 تصفير وإعادة ضبط المدفوعات
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN: Customer details & Fulfillment -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Customer Details Card -->
        <div class="admin-card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 0.75rem; margin-bottom: 1rem; color: var(--admin-heading);">
                👤 معلومات العميل
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.9rem; line-height: 1.6;">
                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">اسم العميل:</span>
                    <strong style="color: var(--admin-heading); font-size: 0.95rem;"><?= esc((string) $order['customer_name']) ?></strong>
                </div>
                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">الهاتف:</span>
                    <strong><a href="tel:<?= esc((string) $order['customer_phone']) ?>" style="color: var(--admin-gold); text-decoration: none;"><?= esc((string) $order['customer_phone']) ?></a></strong>
                </div>
                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">البريد الإلكتروني:</span>
                    <a href="mailto:<?= esc((string) $order['customer_email']) ?>" style="color: var(--admin-heading);"><?= esc((string) $order['customer_email']) ?></a>
                </div>
                <hr style="border: 0; border-top: 1px solid var(--admin-card-border); margin: 0.5rem 0;">
                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">عنوان الشحن:</span>
                    <p style="margin: 0.25rem 0 0; background: var(--admin-card-border); padding: 0.75rem; border-radius: 8px; font-weight: 500;">
                        <?= nl2br(esc((string) $order['shipping_address'])) ?>
                        <?php if (!empty($order['city'])): ?>
                            <br><strong style="color: var(--admin-gold);"><?= esc((string) $order['city']) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (!empty($order['address_landmark'])): ?>
                    <div>
                        <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">علامة مميزة:</span>
                        <span style="font-weight: 600;"><?= esc((string) $order['address_landmark']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- WhatsApp Bot & Payment Receipt Card -->
        <?php
        $isConfirmed = (bool)($order['is_confirmed'] ?? 0);
        $payScope = (string)($order['payment_scope'] ?? 'full');
        $payMethod = (string)($order['payment_method'] ?? 'instapay_wallet');
        $payStatus = (string)($order['payment_status'] ?? 'unpaid');
        $receiptFile = (string)($order['payment_receipt'] ?? '');
        $waPhone = preg_replace('/\D/', '', (string)$order['customer_phone']);
        if (str_starts_with($waPhone, '01') && strlen($waPhone) === 11) $waPhone = '2' . $waPhone;
        ?>
        <div class="admin-card" style="padding: 1.5rem; border: 1px solid <?= !empty($receiptFile) ? '#10b981' : 'var(--admin-card-border)' ?>;">
            <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 0.75rem; margin-bottom: 1rem; color: var(--admin-heading); display: flex; justify-content: space-between; align-items: center;">
                <span>💬 تأكيد الواتساب وإيصال الدفع</span>
                <?php if ($payStatus === 'verified'): ?>
                    <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 50px; font-weight: 800;">تم اعتماد الدفع ✓</span>
                <?php elseif ($payStatus === 'deposit_paid'): ?>
                    <span style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 50px; font-weight: 800;">تم دفع الشحن مقدم ✓</span>
                <?php elseif ($payStatus === 'pending_verification' || !empty($receiptFile)): ?>
                    <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 50px; font-weight: 800;">في انتظار مراجعة الموظف ⏳</span>
                <?php else: ?>
                    <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 50px; font-weight: 800;">بانتظار التحويل والإيصال 📸</span>
                <?php endif; ?>
            </h3>

            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.88rem;">
                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">نظام التحويل:</span>
                    <strong style="color: var(--admin-gold); font-size: 0.95rem;">
                        <?php if ($payScope === 'shipping_only'): ?>
                            🚚 دفع مصاريف الشحن فقط مقدم (<?= number_format((float)$order['shipping_cost'], 2) ?> ج.م)
                        <?php else: ?>
                            💰 دفع كامل قيمة الطلب مقدم (<?= number_format((float)$order['total'], 2) ?> ج.م)
                        <?php endif; ?>
                    </strong>
                </div>

                <div>
                    <span style="color: var(--admin-text-muted); display: block; font-size: 0.8rem;">طريقة التحويل:</span>
                    <strong style="color: var(--admin-heading);">
                        💳 انستاباي / محفظة إلكترونية (فودافون كاش)
                    </strong>
                </div>

                <?php if (!empty($receiptFile)): ?>
                    <div style="margin-top: 0.5rem; background: rgba(245, 158, 11, 0.06); border: 1px solid rgba(245, 158, 11, 0.25); padding: 1rem; border-radius: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                            <span style="color: #d97706; font-weight: 700; font-size: 0.9rem;">📸 صورة إيصال التحويل:</span>
                            <span style="font-size: 0.75rem; color: var(--admin-text-muted);">تم الاستلام عبر الواتساب</span>
                        </div>
                        
                        <a href="<?= esc(url('assets/uploads/receipts/' . $receiptFile)) ?>" target="_blank" style="display: block; text-align: center; margin-bottom: 0.75rem;">
                            <img src="<?= esc(url('assets/uploads/receipts/' . $receiptFile)) ?>" alt="Receipt" style="max-height: 200px; max-width: 100%; border-radius: 8px; border: 1px solid var(--admin-card-border); object-fit: contain; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        </a>

                        <div style="display: flex; gap: 0.5rem;">
                            <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" style="flex: 1; margin: 0;">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                                <input type="hidden" name="action" value="approve_receipt">
                                <button type="submit" class="admin-btn" style="background: #10b981; color: white; width: 100%; font-size: 0.82rem; padding: 0.55rem; font-weight: 700;">✅ اعتماد وتأكيد الدفع</button>
                            </form>
                            <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" style="margin: 0;" onsubmit="return confirm('هل تريد رفض وحذف هذا الإيصال؟');">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                                <input type="hidden" name="action" value="reject_receipt">
                                <button type="submit" class="admin-btn" style="background: #ef4444; color: white; font-size: 0.82rem; padding: 0.55rem; font-weight: 700;">✕ رفض</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: rgba(245, 158, 11, 0.05); border: 1px dashed rgba(245, 158, 11, 0.3); padding: 0.75rem; border-radius: 8px; color: var(--admin-text-muted); font-size: 0.8rem;">
                        ⚠️ لم يتم إرفاق صورة إيصال تحويل بعد.
                    </div>
                <?php endif; ?>

                <!-- Direct WhatsApp Customer Buttons -->
                <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php
                    $waConfirmMsg = "أهلاً بك يا أ/ " . $order['customer_name'] . " من متجر زين للعطور 🌸 بخصوص طلبك رقم " . $order['order_number'] . "، نود تأكيد طلبك وبيانات الشحن.";
                    $waReceiptMsg = "أهلاً بك يا أ/ " . $order['customer_name'] . " من متجر زين للعطور 🌸 نرجو التكرم بإرسال صورة إيصال التحويل (Screenshot) لطلبك رقم " . $order['order_number'] . " لتأكيد الدفع وشحن طلبك فوراً.";
                    ?>
                    <a href="https://wa.me/<?= esc($waPhone) ?>?text=<?= urlencode($waConfirmMsg) ?>" target="_blank" class="admin-btn admin-btn--secondary" style="background: #25d366; color: white; border: none; font-size: 0.82rem; text-align: center; display: block; padding: 0.55rem;">
                        💬 مراسلة العميل بالواتساب لتأكيد الطلب
                    </a>
                    <?php if (empty($receiptFile) && $payMethod !== 'cod'): ?>
                        <a href="https://wa.me/<?= esc($waPhone) ?>?text=<?= urlencode($waReceiptMsg) ?>" target="_blank" class="admin-btn admin-btn--secondary" style="font-size: 0.82rem; text-align: center; display: block; padding: 0.55rem;">
                            📸 طلب صورة إيصال التحويل
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fulfillment & Status Card -->
        <div class="admin-card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 0.75rem; margin-bottom: 1rem; color: var(--admin-heading);">
                📦 حالة الطلب والشحن
            </h3>
            <form method="post" action="<?= esc(admin_url('order_view.php?id=' . $id)) ?>" class="admin-form" style="margin: 0;">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="order_id" value="<?= (int) $id ?>">
                <input type="hidden" name="action" value="update">
                
                <div style="margin-bottom: 1rem;">
                    <label for="status" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">الحالة الحالية:</label>
                    <select name="status" id="status" class="admin-input" style="padding: 0.6rem; font-weight: 700; border-radius: 8px;">
                        <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                            <option value="<?= esc($s) ?>"<?= $order['status'] === $s ? ' selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label for="admin_notes" style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.4rem;">ملاحظات داخلية:</label>
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="admin-input" style="padding: 0.6rem; border-radius: 8px; resize: vertical;" placeholder="أضف ملاحظات لا يراها العميل..."><?= esc((string) ($order['admin_notes'] ?? '')) ?></textarea>
                </div>

                <button type="submit" class="admin-btn admin-btn--primary" style="width: 100%; padding: 0.7rem; font-weight: 700;">💾 حفظ التعديلات</button>
            </form>
        </div>

    </div>
</div>

<p style="margin-top: 2rem;"><a class="admin-btn admin-btn--secondary" href="<?= esc(admin_url('orders.php')) ?>">🔙 العودة لقائمة الطلبات</a></p>

<?php require __DIR__ . '/_layout_end.php'; ?>
