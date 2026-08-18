<?php
$customer = find_customer((int) ($_GET['id'] ?? 0));
if (!$customer) {
    echo '<section class="page-head"><h2>العميل غير موجود</h2><p>لم يتم العثور على العميل المطلوب.</p></section>';
    return;
}
$invoices = customer_invoices((int) $customer['id']);
$debts = customer_debts_rows((int) $customer['id']);
$totalSales = array_sum(array_map(fn($i) => (float) $i['total'], $invoices));
?>
<section class="page-head"><div><h2>ملف العميل: <?= e($customer['name']) ?></h2><p>فواتير العميل، الديون، والملاحظات في صفحة واحدة.</p></div><div class="actions" style="display: flex; gap: 8px; align-items: center;"><a class="btn" href="index.php?r=customers">رجوع للعملاء</a><?php if (has_permission('customers_edit')): ?><form method="post" action="index.php?r=customers" class="inline" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا العميل؟')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($customer['id']) ?>"><button class="btn danger">حذف العميل</button></form><?php endif; ?></div></section>
<section class="cards">
    <article><span>إجمالي المشتريات</span><strong><?= money($totalSales) ?></strong></article>
    <article><span>عدد الفواتير</span><strong><?= e(count($invoices)) ?></strong></article>
    <article><span>ديون مفتوحة</span><strong><?= money(array_sum(array_map(fn($d) => (float) $d['remaining_amount'], array_filter($debts, fn($d) => $d['status'] === 'open')))) ?></strong></article>
</section>
<form class="panel grid-form" method="post" action="index.php?r=customers">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= e($customer['id']) ?>">
    <label>الاسم<input name="name" value="<?= e($customer['name']) ?>" required></label>
    <label>الهاتف<input name="phone" value="<?= e($customer['phone']) ?>"></label>
    <label>المصدر<select name="source"><option value="offline" <?= $customer['source'] === 'offline' ? 'selected' : '' ?>>أوف لاين</option><option value="online" <?= $customer['source'] === 'online' ? 'selected' : '' ?>>أونلاين</option></select></label>
    <label>ملاحظات<input name="notes" value="<?= e($customer['notes']) ?>"></label>
    <button class="btn primary">حفظ التعديل</button>
</form>
<section class="split">
    <div class="panel"><h3>فواتير العميل</h3><?php table_invoices($invoices); ?></div>
    <div class="panel"><h3>ديون العميل</h3><table><thead><tr><th>الفاتورة</th><th>الأصلي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th></tr></thead><tbody><?php foreach ($debts as $d): ?><tr><td><?= e($d['invoice_number']) ?></td><td><?= money($d['original_amount']) ?></td><td><?= money($d['paid_amount']) ?></td><td><?= money($d['remaining_amount']) ?></td><td><span class="badge"><?= e($d['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
</section>
