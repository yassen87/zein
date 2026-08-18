<?php
$defaults = formula_defaults_rows();

// Editing mode
$editId      = (int) ($_GET['edit_id'] ?? 0);
$editDefault = $editId > 0 ? find_formula_default($editId) : null;

$familyOptions = perfume_family_labels();
$gradeOptions = ['' => __('بدون'), 'A' => 'A', 'A+' => 'A+', 'B' => 'B', 'X' => 'X'];

// Get all bottle products for the dropdown
$allProducts = all_products();
$bottleProducts = array_values(array_filter($allProducts, fn($p) => $p['type'] === 'bottle'));
?>

<section class="page-head">
    <div>
        <h2><?= __('الجرامات الافتراضية') ?></h2>
        <p><?= __('تحديد الجرامات الافتراضية لكل زجاجة وعائلة عطر لتسريع إعداد التركيبات.') ?></p>
    </div>
</section>

<section>
    <!-- Add / Edit Form -->
    <form class="panel grid-form" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <?php if ($editDefault): ?>
            <input type="hidden" name="id" value="<?= e($editDefault['id']) ?>">
        <?php endif; ?>

        <h3 style="grid-column: span 4; margin-bottom: 8px;">
            <?= $editDefault ? __('تعديل إعداد الجرامات') : __('إضافة إعداد جرامات افتراضية') ?>
        </h3>

        <label style="grid-column: span 2;"><?= __('الزجاجة') ?>
            <select name="bottle_id" id="bottle-select" required>
                <option value="">-- <?= __('اختر الزجاجة') ?> --</option>
                <?php foreach ($bottleProducts as $bp): ?>
                    <option value="<?= e($bp['id']) ?>" 
                            data-size="<?= e($bp['size_ml']) ?>"
                            <?= $editDefault && (int)$editDefault['bottle_product_id'] === (int)$bp['id'] ? 'selected' : '' ?>>
                        <?= e($bp['name']) ?> (<?= e($bp['size_ml']) ?>ml)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label><?= __('حجم الزجاجة ml') ?>
            <input name="bottle_size_ml" id="bottle-size-display" type="number" readonly
                   value="<?= e($editDefault['bottle_size_ml'] ?? '') ?>"
                   style="background: var(--surface-soft); color: var(--muted);">
        </label>

        <label><?= __('عائلة العطر') ?>
            <select name="perfume_family" id="family-select">
                <?php foreach ($familyOptions as $val => $label): ?>
                    <option value="<?= e($val) ?>"
                        <?= $editDefault && $editDefault['perfume_family'] === $val ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label><?= __('درجة الجودة') ?>
            <select name="quality_grade">
                <?php foreach ($gradeOptions as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= isset($editDefault['quality_grade']) && $editDefault['quality_grade'] === $val ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label><?= __('الجرام الافتراضي') ?>
            <input name="default_grams" type="number" step="1" min="0" required placeholder="12"
                   value="<?= e($editDefault['default_grams'] ?? '') ?>">
        </label>

        <div style="grid-column: span 4; display: flex; gap: 10px; margin-top: 6px;">
            <button class="btn primary">
                <?= $editDefault ? __('حفظ التعديل') : __('حفظ إعداد الجرامات') ?>
            </button>
            <?php if ($editDefault): ?>
                <a href="?r=formula_defaults" class="btn secondary"><?= __('إلغاء') ?></a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Defaults Table -->
    <div class="panel">
        <h3><?= __('جدول الجرامات الافتراضية') ?></h3>
        <table>
            <thead>
                <tr>
                    <th><?= __('الزجاجة') ?></th>
                    <th><?= __('الحجم') ?></th>
                    <th><?= __('عائلة العطر') ?></th>
                    <th><?= __('درجة الجودة') ?></th>
                    <th><?= __('الجرامات') ?></th>
                    <th style="text-align: center;"><?= __('إجراءات') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($defaults): ?>
                    <?php foreach ($defaults as $d): ?>
                        <tr class="<?= $editId === (int)$d['id'] ? 'highlight-row' : '' ?>">
                            <td>
                                <span class="badge">
                                    <?= e($d['bottle_name'] ?: __('زجاجة #') . $d['bottle_product_id']) ?>
                                </span>
                            </td>
                            <td><strong><?= e($d['bottle_size_ml']) ?>ml</strong></td>
                            <td>
                                <span class="badge">
                                    <?= e($familyOptions[$d['perfume_family']] ?? $d['perfume_family']) ?>
                                </span>
                            </td>
                            <td><strong><?= e($d['quality_grade'] ?: '-') ?></strong></td>
                            <td><strong><?= e(qty($d['default_grams'])) ?> <?= __('جرام') ?></strong></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <?php if (has_permission('recipes_add') || has_permission('recipes_edit')): ?>
                                <a href="?r=formula_defaults&amp;edit_id=<?= e($d['id']) ?>" class="btn small secondary">✏️ <?= __('تعديل') ?></a>
                                <?php endif; ?>
                                <?php if (has_permission('recipes_edit')): ?>
                                <form action="index.php?r=formula_defaults" method="post" style="display:inline;" class="confirm-delete">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                    <button type="submit" class="btn small danger">🗑️ <?= __('حذف نهائي') ?></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 20px;">
                            <?= __('لا توجد إعدادات جرامات محفوظة حتى الآن.') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.highlight-row {
    background: var(--primary-alpha, rgba(99, 102, 241, 0.08)) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const familySelect = document.getElementById('family-select');
    if (familySelect) makeSelectSearchable(familySelect);
    
    const bottleSelect = document.getElementById('bottle-select');
    const sizeDisplay = document.getElementById('bottle-size-display');
    if (bottleSelect && sizeDisplay) {
        makeSelectSearchable(bottleSelect);
        bottleSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const size = option.getAttribute('data-size') || '';
            sizeDisplay.value = size;
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const msg = <?= json_encode(__('هل أنت متأكد من حذف هذا الإعداد نهائياً؟')) ?>;
    document.querySelectorAll('form.confirm-delete').forEach(f => {
        f.addEventListener('submit', (e) => {
            if (!confirm(msg)) e.preventDefault();
        });
    });
});
</script>
