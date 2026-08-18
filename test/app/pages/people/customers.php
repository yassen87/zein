<?php
$customers = all_customers();
$debts = debts_rows();
$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $customers = array_values(array_filter($customers, fn($c) => str_contains($c['name'], $search) || str_contains((string) $c['phone'], $search)));
}
?>
<section class="page-head"><h2>العملاء والديون</h2><p>سجل موحد للعملاء مع متابعة الديون والمدفوعات.</p></section>
<form class="panel toolbar" method="get"><input type="hidden" name="r" value="customers"><label>بحث<input name="q" value="<?= e($search) ?>" placeholder="اسم أو هاتف"></label><button class="btn primary">بحث</button><a class="btn" href="index.php?r=customers">إعادة ضبط</a></form>
<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>اسم العميل<input name="name" required></label>
    <label>الهاتف<input name="phone"></label>
    <label>المصدر<select name="source"><option value="offline">أوف لاين</option><option value="online">أونلاين</option></select></label>
    <label>ملاحظات<input name="notes"></label>
    <button class="btn primary">إضافة عميل</button>
</form>
<h3>ديون مفتوحة</h3>
<div class="panel"><table><thead><tr><th>العميل</th><th>الهاتف</th><th>الفاتورة</th><th>الأصلي</th><th>المتبقي</th><th>دفعة</th></tr></thead><tbody>
<?php foreach ($debts as $d): ?><tr><td><?= e($d['customer_name']) ?></td><td><?= e($d['phone']) ?></td><td><?= e($d['invoice_number']) ?></td><td><?= money($d['original_amount']) ?></td><td><?= money($d['remaining_amount']) ?></td><td><form method="post" class="inline pay"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="pay_debt"><input type="hidden" name="debt_id" value="<?= e($d['id']) ?>"><input name="amount" type="number" step="1" min="1" placeholder="مبلغ"><select name="method"><option value="cash">كاش</option><option value="instapay">انستا</option><option value="bank_transfer">تحويل</option><option value="vodafone_cash">فودافون</option></select><button class="btn small">تسديد</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<h3>سجل العملاء</h3>
<div class="panel"><table><thead><tr><th>الاسم</th><th>الهاتف</th><th>المصدر</th><th>ملاحظات</th><th>إجراء</th></tr></thead><tbody>
<?php foreach ($customers as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= e($c['phone']) ?></td><td><?= e($c['source']) ?></td><td><?= e($c['notes']) ?></td><td><a class="btn small" href="index.php?r=customer_view&id=<?= e($c['id']) ?>">ملف العميل</a><?php if (has_permission('customers_edit')): ?><form method="post" class="inline" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا العميل؟')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($c['id']) ?>"><button class="btn small danger">حذف</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
