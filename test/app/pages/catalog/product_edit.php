<?php
$product = find_product((int)($_GET['id'] ?? 0));
if (!$product) { echo '<div class="alert danger">المنتج غير موجود.</div>'; return; }
$familyLabels = perfume_family_labels();
$qualityLabels = quality_grade_labels();
?>
<section class="page-head"><div><h2>تعديل منتج</h2><p>تعديل منتج واحد فقط. لإنشاء عدة أحجام أو جودات استخدم صفحة إضافة منتج.</p></div><a class="btn" href="index.php?r=products">رجوع للمنتجات</a></section>
<form class="panel grid-form product-form-advanced" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
    <h3 style="grid-column: span 4; margin:0; color:var(--primary);">نوع المنتج: <?= e(product_type_label($product['type'])) ?></h3>
    <label>اسم المنتج<input name="name" required value="<?= e($product['name']) ?>"></label>
    <label>باركود<input name="barcode" maxlength="13" value="<?= e($product['barcode'] ?? '') ?>"></label>
    <label>سعر البيع<input name="sale_price" type="number" step="1" min="0" value="<?= e($product['sale_price'] ?? 0) ?>"></label>
    <label>تكلفة الشراء<input name="cost_price" type="number" step="1" min="0" value="<?= e($product['cost_price'] ?? '') ?>"></label>
    <label>حد تنبيه المخزون<input name="min_stock" type="number" step="1" min="0" value="<?= e($product['min_stock'] ?? 0) ?>"></label>
    <?php if ($product['type'] === 'bottle' || $product['type'] === 'fixed'): ?><label>الحجم ml<input name="size_ml" type="number" step="1" min="0" value="<?= e($product['size_ml'] ?? '') ?>"></label><?php endif; ?>
    <?php if ($product['type'] === 'perfume_gram'): ?><label>عائلة العطر<select name="perfume_family"><?php foreach ($familyLabels as $v => $l): ?><option value="<?= e($v) ?>" <?= ($product['perfume_family'] ?? '') === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label><label>الجودة<select name="quality_grade"><?php foreach ($qualityLabels as $v => $l): ?><option value="<?= e($v) ?>" <?= ($product['quality_grade'] ?? '') === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label><label>سعر الجرام<input name="price_per_gram" type="number" step="1" min="0" value="<?= e($product['price_per_gram'] ?? 0) ?>"></label><?php endif; ?>
    <button class="btn primary">حفظ التعديل</button>
</form>
