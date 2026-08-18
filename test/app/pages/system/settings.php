<?php
$settings = [];
foreach (settings_rows() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$labels = [
    'debt_alert_days' => 'عدد أيام تنبيه الدين المتأخر',
    'target_midmonth_threshold' => 'نسبة التارجت المطلوبة في منتصف الشهر',
    'allow_negative_stock' => 'السماح بالمخزون السالب 0/1',
];
?>
<section class="page-head"><h2>الإعدادات</h2><p>إعدادات عامة تتحكم في التنبيهات والمخزون.</p></section>
<form class="panel grid-form" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php foreach ($labels as $key => $label): ?>
        <label><?= e($label) ?><input name="settings[<?= e($key) ?>]" value="<?= e($settings[$key] ?? '') ?>"></label>
    <?php endforeach; ?>
    <button class="btn primary">حفظ الإعدادات</button>
</form>
<div class="panel"><h3>ملاحظات تشغيلية</h3><p class="muted">هذه الإعدادات جاهزة للربط التدريجي مع قواعد التنبيهات والمخزون. القيم الرقمية تحفظ كنصوص داخل قاعدة البيانات لتسهيل إضافة إعدادات جديدة بدون تعديل الجداول.</p></div>
