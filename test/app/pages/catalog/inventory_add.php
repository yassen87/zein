<?php
$locations = stock_locations();
$userLocationId = current_user_location_id();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
}
$products = all_products();
?>
<section class="page-head">
    <div>
        <h2>إضافة مخزون مركزي</h2>
        <p>أضف عدة أصناف دفعة واحدة إلى موقع المخزون.</p>
    </div>
    <div>
        <a class="btn" href="index.php?r=inventory">رجوع إلى سجل الإضافات</a>
    </div>
</section>

<form class="panel" id="inventory-add-form" method="post" action="index.php?r=inventory_add">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <div class="grid-form" style="margin-bottom: 18px; display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
        <label>الموقع<select name="location_id" required>
            <?php foreach ($locations as $location): ?>
                <option value="<?= e($location['id']) ?>"><?= e($location['name']) ?></option>
            <?php endforeach; ?>
        </select></label>
        <div style="display:flex; align-items:flex-end; gap:8px;">
            <button type="button" class="btn secondary" id="inventory-add-row">أضف صنف</button>
            <button type="button" class="btn" id="inventory-reset-form">إعادة تعيين</button>
        </div>
    </div>

    <div class="panel" style="padding: 0; overflow-x: auto;">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px; text-align:right; min-width: 180px;">الصنف</th>
                    <th style="padding: 12px; text-align:right; min-width: 120px;">الكمية</th>
                    <th style="padding: 12px; text-align:right; min-width: 120px;">سعر البيع</th>
                    <th style="padding: 12px; text-align:right; min-width: 120px;">تكلفة الشراء</th>
                    <th style="padding: 12px; text-align:right; min-width: 220px;">ملاحظة</th>
                    <th style="padding: 12px; text-align:center; width: 80px;">إجراء</th>
                </tr>
            </thead>
            <tbody id="inventory-add-items">
                <tr class="inventory-row">
                    <td style="padding: 10px;">
                        <select name="product_id[]" class="inventory-product-select" required>
                            <option value="">-- اختر صنفاً --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= e($product['id']) ?>" data-sale="<?= e($product['sale_price']) ?>" data-cost="<?= e($product['cost_price'] ?? '') ?>"><?= e($product['name']) ?> (<?= e($product['unit'] === 'gram' ? 'جرام' : 'قطعة') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding: 10px; text-align:center;">
                        <input type="number" name="quantity[]" value="1" min="1" step="1" required style="width:100px; text-align:center;">
                    </td>
                    <td style="padding: 10px; text-align:center;">
                        <input type="number" name="sale_price[]" value="0" min="0" step="0.01" style="width:120px; text-align:center;">
                    </td>
                    <td style="padding: 10px; text-align:center;">
                        <input type="number" name="cost_price[]" value="" min="0" step="0.01" style="width:120px; text-align:center;">
                    </td>
                    <td style="padding: 10px;">
                        <input type="text" name="notes[]" placeholder="ملاحظة لكل صنف" style="width:100%;">
                    </td>
                    <td style="padding: 10px; text-align:center;">
                        <button type="button" class="btn small danger inventory-remove-row">حذف</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="muted" id="inventory-add-hint" style="margin: 12px;">أضف صنفاً واحداً على الأقل ثم اضغط حفظ.</p>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 14px;">
        <button type="submit" class="btn primary">حفظ الإضافات</button>
    </div>
</form>

<template id="inventory-row-template">
    <tr class="inventory-row">
        <td style="padding: 10px;">
            <select name="product_id[]" class="inventory-product-select" required>
                <option value="">-- اختر صنفاً --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e($product['id']) ?>" data-sale="<?= e($product['sale_price']) ?>" data-cost="<?= e($product['cost_price'] ?? '') ?>"><?= e($product['name']) ?> (<?= e($product['unit'] === 'gram' ? 'جرام' : 'قطعة') ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="padding: 10px; text-align:center;">
            <input type="number" name="quantity[]" value="1" min="1" step="1" required style="width:100px; text-align:center;">
        </td>
        <td style="padding: 10px; text-align:center;">
            <input type="number" name="sale_price[]" value="0" min="0" step="0.01" style="width:120px; text-align:center;">
        </td>
        <td style="padding: 10px; text-align:center;">
            <input type="number" name="cost_price[]" value="" min="0" step="0.01" style="width:120px; text-align:center;">
        </td>
        <td style="padding: 10px;">
            <input type="text" name="notes[]" placeholder="ملاحظة لكل صنف" style="width:100%;">
        </td>
        <td style="padding: 10px; text-align:center;">
            <button type="button" class="btn small danger inventory-remove-row">حذف</button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addRowButton = document.getElementById('inventory-add-row');
    const resetButton = document.getElementById('inventory-reset-form');
    const itemsBody = document.getElementById('inventory-add-items');
    const rowTemplate = document.getElementById('inventory-row-template');
    const form = document.getElementById('inventory-add-form');

    function attachRowListeners(row) {
        const productSelect = row.querySelector('.inventory-product-select');
        const saleInput = row.querySelector('input[name="sale_price[]"]');
        const costInput = row.querySelector('input[name="cost_price[]"]');
        const removeButton = row.querySelector('.inventory-remove-row');

        if (typeof makeSelectSearchable === 'function') {
            makeSelectSearchable(productSelect);
        }

        if (productSelect) {
            productSelect.addEventListener('change', () => {
                const option = productSelect.options[productSelect.selectedIndex];
                if (!option || !option.value) {
                    saleInput.value = '0';
                    costInput.value = '';
                    return;
                }
                saleInput.value = option.dataset.sale ?? '0';
                costInput.value = option.dataset.cost ?? '';
            });
        }

        removeButton.addEventListener('click', () => {
            if (itemsBody.querySelectorAll('.inventory-row').length <= 1) {
                resetInventoryRows();
                return;
            }
            row.remove();
        });
    }

    function resetInventoryRows() {
        itemsBody.innerHTML = '';
        const firstRow = document.createElement('tbody');
        const clone = rowTemplate.content.cloneNode(true);
        itemsBody.appendChild(clone);
        const newRow = itemsBody.querySelector('.inventory-row');
        attachRowListeners(newRow);
    }

    addRowButton.addEventListener('click', () => {
        const clone = rowTemplate.content.cloneNode(true);
        itemsBody.appendChild(clone);
        const newRow = itemsBody.querySelector('.inventory-row:last-child');
        attachRowListeners(newRow);
    });

    resetButton.addEventListener('click', (e) => {
        e.preventDefault();
        resetInventoryRows();
    });

    form.addEventListener('submit', (e) => {
        const selectedProductIds = Array.from(form.querySelectorAll('select[name="product_id[]"]'))
            .map(select => select.value)
            .filter(Boolean);
        if (selectedProductIds.length === 0) {
            e.preventDefault();
            alert('يرجى إضافة صنف واحد على الأقل قبل حفظ الإضافات.');
            return;
        }
    });

    const initialRow = itemsBody.querySelector('.inventory-row');
    if (initialRow) {
        attachRowListeners(initialRow);
    }
});
</script>
