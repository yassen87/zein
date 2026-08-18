<?php
$invoices = recent_invoices(100);
$lines = recent_returnable_lines();
$returns = returns_rows();
?>
<section class="page-head"><h2>المرتجعات</h2><p>مرتجع كامل للفاتورة أو مرتجع بند واحد مع إرجاع مكونات المخزون تلقائياً.</p></section>
<section class="split">
<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="return_type" value="invoice">
    <label>الفاتورة<select name="invoice_id"><?php foreach ($invoices as $i): ?><option value="<?= e($i['id']) ?>"><?= e($i['invoice_number']) ?> - <?= money($i['total']) ?></option><?php endforeach; ?></select></label>
    <label>طريقة رد المبلغ<select name="refund_method"><option value="cash">كاش</option><option value="instapay">انستا</option><option value="bank_transfer">تحويل</option><option value="vodafone_cash">فودافون</option><option value="customer_credit">رصيد عميل</option></select></label>
    <label>السبب<input name="reason" required></label>
    <button class="btn primary">مرتجع فاتورة كاملة</button>
</form>
<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="return_type" value="line">
    <label>بند الفاتورة<select name="line_id"><?php foreach ($lines as $line): ?><option value="<?= e($line['line_id']) ?>"><?= e($line['invoice_number']) ?> - <?= e($line['description']) ?> - <?= money($line['line_total']) ?></option><?php endforeach; ?></select></label>
    <label>طريقة رد المبلغ<select name="refund_method"><option value="cash">كاش</option><option value="instapay">انستا</option><option value="bank_transfer">تحويل</option><option value="vodafone_cash">فودافون</option><option value="customer_credit">رصيد عميل</option></select></label>
    <label>السبب<input name="reason" required></label>
    <button class="btn primary">مرتجع بند واحد</button>
</form>
</section>
<div class="panel"><table><thead><tr><th>رقم المرتجع</th><th>الفاتورة</th><th>المبلغ</th><th>طريقة الرد</th><th>المسؤول</th><th>التاريخ</th></tr></thead><tbody><?php foreach ($returns as $r): ?><tr><td><?= e($r['return_number']) ?></td><td><?= e($r['invoice_number']) ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['refund_method']) ?></td><td><?= e($r['user_name']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
