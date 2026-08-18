<?php
$typeLabels = array_filter(product_type_labels(), fn($k) => in_array($k, ['bottle','perfume_gram','fixed'], true), ARRAY_FILTER_USE_KEY);
$familyLabels = perfume_family_labels();
$qualityLabels = quality_grade_labels();
?>
<section class="page-head product-create-head">
    <div>
        <h2>➕ إضافة منتج جديد</h2>
        <p>كل حجم أو جودة يتم إنشاؤها كمنتج مستقل وله باركود خاص وسعر بيع وشراء مستقل.</p>
    </div>
    <a class="btn" href="index.php?r=products">← رجوع للمنتجات</a>
</section>

<form class="product-create-layout" method="post" id="product-create-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <div class="panel product-create-card product-type-card">
        <h3>1) اختر نوع المنتج</h3>
        <p class="muted">الخانات غير المسموح بها تختفي بالكامل حسب نوع المنتج.</p>
        <div class="product-type-options">
            <?php foreach ($typeLabels as $v => $l): ?>
                <label class="product-type-option" data-type-card="<?= e($v) ?>">
                    <input type="radio" name="type" value="<?= e($v) ?>" <?= $v === 'bottle' ? 'checked' : '' ?>>
                    <span class="product-type-icon"><?= $v === 'bottle' ? '🧴' : ($v === 'perfume_gram' ? '🧪' : '📦') ?></span>
                    <strong><?= e($l) ?></strong>
                    <small><?= $v === 'perfume_gram' ? 'أضف أكثر من جودة بأسعار مختلفة' : 'أضف أكثر من حجم بأسعار مختلفة' ?></small>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel product-create-card">
        <h3>2) البيانات الأساسية</h3>
        <div class="grid-form product-create-grid">
            <label>اسم المنتج الأساسي
                <input name="name" required placeholder="مثال: كلاسيك / دهن عود / عبوة جاهزة">
                <small class="muted">سيتم إضافة الحجم أو الجودة تلقائياً لاسم كل منتج.</small>
            </label>
            <label>حد تنبيه المخزون
                <input name="min_stock" type="number" step="1" min="0" value="0">
            </label>
        </div>
    </div>

    <div class="panel product-create-card type-section" data-product-field="bottle fixed">
        <h3>3) الأحجام والأسعار</h3>
        <p class="muted">كل صف = منتج مستقل بباركود مستقل. اكتب سعر بيع وسعر شراء لكل حجم.</p>
        <div class="variant-table-wrap">
            <table class="variant-table" id="size-variants-table">
                <thead><tr><th>الحجم ml</th><th>سعر البيع</th><th>سعر الشراء</th><th></th></tr></thead>
                <tbody id="size-variants-body">
                    <tr>
                        <td><input name="variants[size][]" type="number" step="1" min="1" placeholder="100" required></td>
                        <td><input name="variants[sale_price][]" type="number" step="1" min="0" value="0" required></td>
                        <td><input name="variants[cost_price][]" type="number" step="1" min="0" placeholder="0"></td>
                        <td><button type="button" class="btn small danger" onclick="removeVariantRow(this)">حذف</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn secondary" onclick="addSizeVariantRow()">+ إضافة حجم آخر</button>
    </div>

    <div class="panel product-create-card type-section" data-product-field="perfume_gram">
        <h3>3) الجودات والأسعار</h3>
        <p class="muted">كل صف = منتج مستقل بباركود مستقل. اكتب سعر الجرام وسعر الشراء لكل جودة.</p>
        <div class="grid-form product-create-grid" style="margin-bottom:10px;">
            <label>عائلة العطر
                <select name="perfume_family">
                    <?php foreach ($familyLabels as $v => $l): ?>
                        <option value="<?= e($v) ?>"><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="variant-table-wrap">
            <table class="variant-table" id="quality-variants-table">
                <thead><tr><th>الجودة</th><th>سعر الجرام</th><th>سعر الشراء</th><th></th></tr></thead>
                <tbody id="quality-variants-body">
                    <tr>
                        <td>
                            <select name="variants[quality][]" required>
                                <?php foreach ($qualityLabels as $v => $l): ?>
                                    <option value="<?= e($v) ?>" <?= $v === 'A' ? 'selected' : '' ?>><?= e($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input name="variants[price_per_gram][]" type="number" step="1" min="0" value="0" required></td>
                        <td><input name="variants[cost_price][]" type="number" step="1" min="0" placeholder="0"></td>
                        <td><button type="button" class="btn small danger" onclick="removeVariantRow(this)">حذف</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn secondary" onclick="addQualityVariantRow()">+ إضافة جودة أخرى</button>
    </div>

    <div class="product-create-actions panel">
        <button class="btn primary" style="min-width:180px;">حفظ وإنشاء المنتجات</button>
        <a class="btn" href="index.php?r=products">إلغاء</a>
    </div>
</form>

<script>
const qualityOptionsHtml = <?= json_encode(implode('', array_map(fn($v, $l) => '<option value="' . e($v) . '">' . e($l) . '</option>', array_keys($qualityLabels), $qualityLabels)), JSON_UNESCAPED_UNICODE) ?>;

function currentProductType() {
    const checked = document.querySelector('input[name="type"]:checked');
    return checked ? checked.value : 'bottle';
}

function toggleProductFields() {
    const type = currentProductType();
    document.querySelectorAll('[data-type-card]').forEach(card => card.classList.toggle('active', card.dataset.typeCard === type));
    document.querySelectorAll('[data-product-field]').forEach(section => {
        const allowedTypes = section.dataset.productField.split(/\s+/);
        const show = allowedTypes.includes(type);
        section.hidden = !show;
        section.style.display = show ? '' : 'none';
        section.querySelectorAll('input, select, textarea, button').forEach(input => input.disabled = !show);
    });
}

function addSizeVariantRow() {
    document.getElementById('size-variants-body').insertAdjacentHTML('beforeend', `
        <tr>
            <td><input name="variants[size][]" type="number" step="1" min="1" placeholder="100" required></td>
            <td><input name="variants[sale_price][]" type="number" step="1" min="0" value="0" required></td>
            <td><input name="variants[cost_price][]" type="number" step="1" min="0" placeholder="0"></td>
            <td><button type="button" class="btn small danger" onclick="removeVariantRow(this)">حذف</button></td>
        </tr>
    `);
}

function addQualityVariantRow() {
    document.getElementById('quality-variants-body').insertAdjacentHTML('beforeend', `
        <tr>
            <td><select name="variants[quality][]" required>${qualityOptionsHtml}</select></td>
            <td><input name="variants[price_per_gram][]" type="number" step="1" min="0" value="0" required></td>
            <td><input name="variants[cost_price][]" type="number" step="1" min="0" placeholder="0"></td>
            <td><button type="button" class="btn small danger" onclick="removeVariantRow(this)">حذف</button></td>
        </tr>
    `);
}

function removeVariantRow(btn) {
    const tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length <= 1) {
        alert('يجب ترك صف واحد على الأقل.');
        return;
    }
    btn.closest('tr').remove();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="type"]').forEach(input => input.addEventListener('change', toggleProductFields));
    toggleProductFields();
});
</script>
