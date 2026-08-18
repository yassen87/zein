<?php
$invoice = find_invoice((int) ($_GET['id'] ?? 0));
if (!$invoice) {
    echo '<section class="page-head"><h2>الفاتورة غير موجودة</h2><p>لم يتم العثور على الفاتورة المطلوبة.</p></section>';
    return;
}
$lines = invoice_lines_rows((int) $invoice['id']);
$payments = invoice_payments_rows((int) $invoice['id']);
$allFormulaDefaults = formula_defaults_rows();

function invoice_component_modified_info(array $component, array $allFormulaDefaults, ?int $bottleSizeMl = null): array
{
    $result = ['isModified' => false, 'defaultQty' => 0];
    if (($component['unit'] ?? '') !== 'gram' || !empty($component['size_ml'])) {
        return $result;
    }

    $stmt = pdo()->prepare('SELECT perfume_family, quality_grade FROM product_perfume_details WHERE product_id = ?');
    $stmt->execute([(int) $component['component_product_id']]);
    $perfumeDetails = $stmt->fetch();
    if (!$perfumeDetails) {
        return $result;
    }

    $family = $perfumeDetails['perfume_family'];
    $grade = $perfumeDetails['quality_grade'] ?: '';
    $qty = (float) $component['quantity'];

    foreach ($allFormulaDefaults as $fd) {
        if ($fd['perfume_family'] === $family
            && ($fd['quality_grade'] ?: '') === $grade
            && ($bottleSizeMl === null || (int) $fd['bottle_size_ml'] === $bottleSizeMl)
        ) {
            $result['defaultQty'] = (float) $fd['default_grams'];
            break;
        }
    }

    if ($result['defaultQty'] > 0 && abs($qty - $result['defaultQty']) > 0.01) {
        $result['isModified'] = true;
    }

    return $result;
}

function invoice_line_bottle_size(array $components): ?int
{
    foreach ($components as $c) {
        if (!empty($c['size_ml'])) {
            return (int) $c['size_ml'];
        }
    }
    return null;
}

$typeTranslations = [
    'product' => 'جاهز',
    'custom_recipe' => 'تركيبة فورية',
    'saved_recipe' => 'تركيبة جاهزة'
];
$paymentTranslations = [
    'cash' => 'كاش',
    'instapay' => 'انستا باي',
    'bank_transfer' => 'تحويل بنكي',
    'vodafone_cash' => 'فودافون كاش',
    'invoice_payment' => 'دفع فاتورة',
    'debt_payment' => 'سداد دين'
];
?>
<div class="screen-only">
    <section class="page-head print-hide">
        <div>
            <h2>تفاصيل الفاتورة <?= e($invoice['invoice_number']) ?></h2>
            <p>عرض كامل للبنود والمكونات والمدفوعات.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a class="btn" href="index.php?r=pos">العودة لشاشة الكاشير</a>
            <button class="btn primary" onclick="openCashDrawer(); window.print();">طباعة الفاتورة 🖨️</button>
        </div>
    </section>

    <section class="invoice-paper">
        <header class="invoice-header">
            <div><h2><?= e(APP_NAME) ?></h2><p>فاتورة بيع</p></div>
            <div><strong><?= e($invoice['invoice_number']) ?></strong><span><?= e($invoice['created_at']) ?></span></div>
        </header>
        <section class="detail-grid">
            <div><span>الفرع</span><strong><?= e(str_replace(["فرع 1","فرع 2"], ["ام خنان","المنوات"], $invoice['location_name'])) ?></strong></div>
            <div><span>الموظف</span><strong><?= e($invoice['user_name']) ?></strong></div>
            <div><span>العميل</span><strong><?= e($invoice['customer_name'] ?: 'زبون عابر') ?></strong></div>
            <div><span>الهاتف</span><strong><?= e($invoice['customer_phone'] ?: '-') ?></strong></div>
        </section>
        <table>
            <thead><tr><th>الوصف</th><th>النوع</th><th>الكمية</th><th>السعر</th><th>خصم</th><th>الإجمالي</th></tr></thead>
            <tbody>
            <?php foreach ($lines as $line): ?>
                <?php $components = invoice_components_rows((int) $line['id']); $bottleSize = invoice_line_bottle_size($components); ?>
                <tr>
                    <td><?= e($line['description']) ?></td>
                    <td><span class="badge"><?= e($typeTranslations[$line['line_type']] ?? $line['line_type']) ?></span></td>
                    <td><?= e(qty($line['quantity'])) ?></td>
                    <td><?= money($line['unit_price']) ?></td>
                    <td><?= money($line['discount_amount']) ?></td>
                    <td><?= money($line['line_total']) ?></td>
                </tr>
                <?php if ($components): ?>
                    <tr class="sub-row"><td colspan="6">
                        <?php foreach ($components as $c): ?>
                            <span class="chip">
                                <?= e($c['product_name']) ?><?= $c['size_ml'] ? ' (' . (int)$c['size_ml'] . 'ml)' : '' ?>
                            </span>
                        <?php endforeach; ?>
                    </td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <section class="totals-box">
            <div><span>قبل الخصم</span><strong><?= money($invoice['subtotal']) ?></strong></div>
            <div><span>خصم الفاتورة</span><strong><?= money($invoice['discount_amount']) ?></strong></div>
            <div><span>الإجمالي</span><strong><?= money($invoice['total']) ?></strong></div>
            <div><span>المدفوع</span><strong><?= money($invoice['paid_total']) ?></strong></div>
            <div><span>المتبقي</span><strong><?= money($invoice['due_total']) ?></strong></div>
        </section>
        <h3>المدفوعات</h3>
        <table><thead><tr><th>الطريقة</th><th>المبلغ</th><th>النوع</th><th>المسؤول</th><th>التاريخ</th></tr></thead><tbody><?php foreach ($payments as $p): ?><tr><td><?= e($paymentTranslations[$p['method']] ?? $p['method']) ?></td><td><?= money($p['amount']) ?></td><td><?= e($paymentTranslations[$p['payment_type']] ?? $p['payment_type']) ?></td><td><?= e($p['user_name']) ?></td><td><?= e($p['created_at']) ?></td></tr><?php endforeach; ?></tbody></table>
        <?php if ($invoice['notes']): ?><p class="invoice-note"><strong>ملاحظة:</strong> <?= e($invoice['notes']) ?></p><?php endif; ?>
    </section>
</div>

<div class="print-only receipt-container">
    <div class="receipt-header">
        <div class="receipt-logo"><img src="<?= e(APP_BASE) ?>/assets/logo.png" alt="logo" /></div>
        <div><h2><?= e(APP_NAME) ?></h2><p>الفرع: <?= e(str_replace(["فرع 1","فرع 2"], ["ام خنان","المنوات"], $invoice['location_name'])) ?></p><p>التاريخ: <?= e($invoice['created_at']) ?></p></div>
    </div>
    <div class="receipt-meta">
        <div><strong>رقم الفاتورة:</strong> <span><?= e($invoice['invoice_number']) ?></span></div>
        <div><strong>العميل:</strong> <span><?= e($invoice['customer_name'] ?: 'زبون عابر') ?></span></div>
        <?php if ($invoice['customer_phone']): ?><div><strong>الهاتف:</strong> <span><?= e($invoice['customer_phone']) ?></span></div><?php endif; ?>
    </div>
    <div class="receipt-divider"></div>
    <div class="receipt-items">
        <?php foreach ($lines as $line): ?>
            <?php $components = invoice_components_rows((int) $line['id']); $bottleSize = invoice_line_bottle_size($components); ?>
            <div class="receipt-item"><div class="item-name"><?= e($line['description']) ?></div><div class="item-calc"><span><?= e(qty($line['quantity'])) ?> × <?= e(money($line['unit_price'])) ?></span><strong><?= e(money($line['line_total'])) ?></strong></div></div>
            <?php if ($components): ?>
                <div class="receipt-item-components">
                    <?php foreach ($components as $c): ?>
                        <?php $modified = invoice_component_modified_info($c, $allFormulaDefaults, $bottleSize); ?>
                        <span>- <?= e($c['product_name']) ?><?= $c['size_ml'] ? ' (' . (int)$c['size_ml'] . 'ml)' : '' ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="receipt-divider"></div>
    <div class="receipt-summary">
        <div><span>إجمالي الأصناف:</span> <strong><?= money($invoice['subtotal']) ?></strong></div>
        <?php if ((float)$invoice['discount_amount'] > 0): ?><div><span>الخصم:</span> <strong><?= money($invoice['discount_amount']) ?></strong></div><?php endif; ?>
        <div class="receipt-total"><span>المطلوب:</span> <strong><?= money($invoice['total']) ?></strong></div>
        <div><span>المدفوع:</span> <strong><?= money($invoice['paid_total']) ?></strong></div>
        <?php if ((float)$invoice['due_total'] > 0): ?><div class="receipt-due"><span>المتبقي (دين):</span> <strong><?= money($invoice['due_total']) ?></strong></div><?php endif; ?>
    </div>
    <div class="receipt-divider"></div>
    <div class="receipt-barcode"><code><?= e($invoice['invoice_number']) ?></code></div>
    <div class="receipt-footer"><p>شكراً لزيارتكم! نتشرف بلقائكم دائماً</p></div>
</div>

<script>
function openCashDrawer() {
    try {
        // Browser security does not allow direct ESC/POS drawer pulses.
        // Printing the receipt is what triggers the drawer if the printer driver is configured to open it before/after print.
        window.dispatchEvent(new Event('cash-drawer-open'));
    } catch (e) {}
}

<?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?>
window.onload = function() {
    localStorage.removeItem('pos_cart');
    sessionStorage.removeItem('pos_invoice_submit_pending');
    openCashDrawer();
    setTimeout(function() { window.print(); }, 300);
}
<?php endif; ?>
</script>
