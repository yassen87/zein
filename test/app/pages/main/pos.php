<?php
$locations = sale_locations();
$userLocationId = current_user_location_id();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
}
$products = array_values(array_filter(all_products(), fn($p) => $p['type'] !== 'recipe'));
$customers = all_customers();
$bottles = array_values(array_filter($products, fn($p) => $p['type'] === 'bottle'));
$perfumes = array_values(array_filter($products, fn($p) => $p['type'] === 'perfume_gram'));
$recipes = saved_recipes();
$defaults = formula_defaults_rows();
?>
<script>
    const formulaDefaults = <?= json_encode($defaults) ?>;
</script>

<style>
/* CSS Styles for the Cashier/POS Page */
.pos-container {
    display: grid;
    grid-template-columns: 2.2fr 1fr !important;
    gap: 20px !important;
    align-items: start;
    margin-top: 15px;
}

@media (max-width: 1024px) {
    .pos-container {
        grid-template-columns: 1fr !important;
    }
}

/* Glassmorphism Panel styles */
.pos-main-panel .panel, 
.pos-side-panel .panel {
    background: var(--surface) !important;
    border-radius: 16px !important;
    border: 1px solid var(--line) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
    padding: 24px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden;
}

/* Card titles */
.pos-main-panel .panel h3,
.pos-side-panel .panel h3 {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: var(--ink) !important;
    margin-top: 0 !important;
    margin-bottom: 20px !important;
    padding-bottom: 12px !important;
    border-bottom: 1px solid var(--line) !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Barcode Scanner Bar modern design */
.barcode-scanner-bar {
    background: var(--surface) !important;
    border: 1.5px solid var(--line) !important;
    border-radius: 12px !important;
    padding: 12px 20px !important;
    margin-bottom: 20px !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02) !important;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.barcode-scanner-bar:focus-within {
    border-color: var(--primary) !important;
    box-shadow: 0 4px 20px rgba(124, 58, 237, 0.08) !important;
}

/* Status Indicator Pulse Animation */
.scanner-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--muted);
    font-size: 13px;
    white-space: nowrap;
}

.scanner-status-dot {
    width: 10px;
    height: 10px;
    background-color: var(--success);
    border-radius: 50%;
    position: relative;
}

.scanner-status-dot::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--success);
    border-radius: 50%;
    animation: pulse 1.8s infinite ease-in-out;
}

@keyframes pulse {
    0% {
        transform: scale(0.9);
        opacity: 0.8;
    }
    100% {
        transform: scale(2.4);
        opacity: 0;
    }
}

/* Segmented Tabs (Pill controls) */
.segmented-tabs {
    display: flex;
    background: var(--surface-soft);
    padding: 4px;
    border-radius: 10px;
    border: 1px solid var(--line);
    margin-bottom: 20px;
    gap: 2px;
}

.segmented-tabs button {
    flex: 1;
    border: none;
    background: transparent;
    color: var(--muted);
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.segmented-tabs button.active {
    background: var(--surface);
    color: var(--primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Form Styles */
.grid-form {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.grid-form label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
}

.grid-form input, 
.grid-form select, 
.grid-form textarea {
    padding: 10px 14px !important;
    font-size: 13px !important;
    border: 1.5px solid var(--line) !important;
    border-radius: 8px !important;
    background: var(--surface) !important;
    color: var(--ink) !important;
    transition: all 0.2s ease !important;
    font-family: inherit;
    outline: none;
}

.grid-form input:focus, 
.grid-form select:focus, 
.grid-form textarea:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1) !important;
}

/* Table Style for Shopping Cart */
table.cart-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    margin-top: -8px;
}

table.cart-table th {
    font-weight: 700;
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    padding: 10px 12px;
    border: none;
    text-align: right;
}

table.cart-table tbody tr {
    background: var(--surface-soft);
    transition: all 0.2s ease;
}

table.cart-table tbody tr:hover {
    background: var(--primary-soft) !important;
    transform: translateY(-1px);
}

table.cart-table td {
    padding: 14px 12px;
    border: none;
    vertical-align: middle;
}

table.cart-table td:first-child {
    border-radius: 0 10px 10px 0;
}

table.cart-table td:last-child {
    border-radius: 10px 0 0 10px;
}

/* Quantity controls */
.qty-control {
    display: inline-flex !important;
    align-items: center !important;
    border: 1.5px solid var(--line) !important;
    border-radius: 8px !important;
    overflow: hidden !important;
    background: var(--surface) !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
    height: 32px !important;
}

.qty-control button {
    border: none !important;
    background: var(--surface-soft) !important;
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    font-size: 16px !important;
    font-weight: bold !important;
    cursor: pointer !important;
    color: var(--ink) !important;
    transition: background 0.15s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.qty-control button:hover {
    background: var(--line) !important;
}

.qty-control input {
    width: 40px !important;
    height: 32px !important;
    text-align: center !important;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    outline: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: textfield !important;
}

.qty-control input::-webkit-outer-spin-button,
.qty-control input::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}

/* Receipt Display */
.receipt-box {
    background: var(--surface-soft);
    border: 1px dashed var(--line);
    border-radius: 12px;
    padding: 16px;
    margin-top: 15px;
    margin-bottom: 15px;
    display: grid;
    gap: 10px;
}

.receipt-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.receipt-row.total {
    border-top: 1px dashed var(--line);
    padding-top: 12px;
    font-size: 16px;
    font-weight: 800;
    color: var(--ink);
}

/* Custom badges */
.badge-type {
    font-size: 10px !important;
    padding: 3px 8px !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    display: inline-block;
    margin-right: 6px;
}

.badge-type.direct {
    background: rgba(124, 58, 237, 0.1);
    color: var(--primary);
}

.badge-type.recipe {
    background: rgba(6, 182, 212, 0.1);
    color: var(--accent);
}

.badge-type.custom {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

/* Split payment visual container */
#split-payment-container {
    background: rgba(124, 58, 237, 0.03);
    border: 1.5px dashed var(--primary);
    border-radius: 12px;
    padding: 14px;
    margin-top: 10px;
    display: none;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

#split-payment-container label {
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

#split-payment-container input {
    background: var(--surface) !important;
    border: 1px solid var(--line) !important;
    padding: 8px 12px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    text-align: center;
}

#split-payment-container input:focus {
    border-color: var(--primary) !important;
}

/* Big Checkout Button */
.btn-checkout {
    background: var(--primary) !important;
    color: #fff !important;
    border: none !important;
    border-radius: 12px !important;
    padding: 14px 20px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25) !important;
}

.btn-checkout:hover {
    background: var(--primary-dark) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(124, 58, 237, 0.35) !important;
}

.btn-checkout:active {
    transform: translateY(0) !important;
}

.btn-delete-item {
    background: rgba(220, 38, 38, 0.08) !important;
    color: var(--danger) !important;
    border: none !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    transition: all 0.15s ease !important;
}

.btn-delete-item:hover {
    background: var(--danger) !important;
    color: #fff !important;
}
</style>

<section class="page-head">
    <div>
        <h2>شاشة البيع والكاشير POS</h2>
        <p>إدارة المبيعات الفورية، والتركيبات الفورية والوصفات الجاهزة مع احتساب فوري للقيم والخصومات.</p>
    </div>
</section>

<!-- Barcode Scanner Input -->
<div class="barcode-scanner-bar">
    <div class="scanner-status">
        <span class="scanner-status-dot"></span>
        <span>القارئ التلقائي نشط</span>
    </div>
    <input type="text" id="barcode-scanner-input" autocomplete="off" placeholder="امسح الباركود ضوئياً أو اكتبه (نشط تلقائياً من أي مكان في الصفحة)..." style="flex: 1; direction: ltr; text-align: left; font-family: monospace; letter-spacing: 1px;">
    <button type="button" class="btn small primary" id="barcode-lookup-btn" style="padding: 10px 20px; font-size: 13px; font-weight: 600; border-radius: 8px;">بحث</button>
    <div id="barcode-feedback" style="font-size: 13px; font-weight: 700; color: var(--muted); min-width: 120px; text-align: center;"></div>
</div>

<form class="pos-container" method="post" id="pos-main-form">
    <!-- Left Column: Basket / Products / Mixes -->
    <div class="pos-main-panel">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <!-- Hidden fields generated by JavaScript for checkout -->
        <div id="hidden-cart-inputs"></div>

        <!-- Section 1: Add Items to Basket -->
        <div class="panel">
            <h3 style="border-bottom: 1px solid var(--line); padding-bottom: 6px; margin-bottom: 12px; color: var(--primary);">
                إضافة الأصناف والمبيعات</h3>

            <div class="segmented-tabs">
                <button type="button" class="active" id="tab-direct-btn" onclick="switchAddTab('direct')">📦 منتج مباشر / جاهز</button>
                <button type="button" id="tab-recipe-btn" onclick="switchAddTab('recipe')">📜 تركيبة جاهزة (وصفة)</button>
                <button type="button" id="tab-mix-btn" onclick="switchAddTab('mix')">🧪 تركيبة فورية (تفصيل)</button>
            </div>

            <!-- Tab Content 1: Direct Products -->
            <div id="add-direct-panel" class="grid-form">
                <label style="grid-column: span 2;">اختر المنتج
                    <select id="add-prod-select">
                        <option value="">-- اختر صنف مباشر --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= e($p['id']) ?>" data-name="<?= e($p['name']) ?>"
                                data-price="<?= e($p['sale_price']) ?>"><?= e($p['name']) ?> -
                                (<?= money($p['sale_price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>الكمية
                    <input id="add-prod-qty" type="number" step="1" min="1" value="1">
                </label>
                <label>سعر الوحدة
                    <input id="add-prod-price" type="number" step="1" min="0" value="0">
                </label>
                <label>نوع خصم الصنف
                    <select id="add-prod-discount-type">
                        <option value="">بدون</option>
                        <option value="amount">مبلغ</option>
                        <option value="percent">%</option>
                    </select>
                </label>
                <label>قيمة الخصم
                    <input id="add-prod-discount-value" type="number" step="1" min="0" value="0">
                </label>
                <button type="button" class="btn primary" id="add-prod-btn" onclick="addProductToCart()"
                    style="grid-column: span 2; height: 36px; border-radius: 8px;">إضافة صنف للسلة</button>
            </div>

            <!-- Tab Content 2: Saved Recipes -->
            <div id="add-recipe-panel" class="grid-form" style="display: none;">
                <label style="grid-column: span 2;">اختر الوصفة الجاهزة
                    <select id="add-recipe-select">
                        <option value="">-- اختر تركيبة جاهزة --</option>
                        <?php foreach ($recipes as $r): ?>
                            <option value="<?= e($r['id']) ?>" data-name="<?= e($r['name']) ?>"
                                data-price="<?= e($r['default_sale_price']) ?>"><?= e($r['name']) ?> -
                                (<?= money($r['default_sale_price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>الكمية (عدد الزجاجات)
                    <input id="add-recipe-qty" type="number" step="1" min="1" value="1">
                </label>
                <label>السعر
                    <input id="add-recipe-price" type="number" step="1" min="0" value="0" readonly>
                </label>
                <button type="button" class="btn primary" id="add-recipe-btn" onclick="addRecipeToCart()"
                    style="grid-column: span 2; height: 36px; border-radius: 8px;">إضافة التركيبة للسلة</button>
            </div>

            <!-- Tab Content 3: Instant Mix (تركيبة فورية) -->
            <div id="add-mix-panel" class="grid-form" style="display: none;">
                <label style="grid-column: span 2;">نوع الزجاجة المستخدمة
                    <select id="mix_bottle_id">
                        <option value="">-- اختر الزجاجة --</option>
                        <?php foreach ($bottles as $b): ?>
                            <option value="<?= e($b['id']) ?>" data-price="<?= e($b['sale_price']) ?>" data-size="<?= e($b['size_ml']) ?>"><?= e($b['name']) ?> (<?= e($b['size_ml']) ?>ml) - (<?= money($b['sale_price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>سعر البيع الإجمالي للتركيبة
                    <input id="mix_sale_price" type="number" step="1" min="0" value="0" placeholder="اكتب السعر النهائي" data-manual="0" oninput="markMixPriceManual(); recalculateTotals()">
                </label>
                <div style="grid-column: span 2; display: flex; justify-content: space-between; align-items: center; margin-top: 4px; margin-bottom: 4px;">
                    <h4 style="font-size: 12.5px; font-weight: 700; color: var(--muted); margin: 0;">مكونات الزيوت العطرية بالجرام</h4>
                    <button type="button" class="btn small primary" id="add-oil-row-btn" onclick="addOilRow()" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;">+ إضافة زيت عطري</button>
                </div>
                <div id="mix-perfumes-container" style="grid-column: span 2; display: grid; gap: 6px;">
                    <!-- First row default -->
                    <div class="line-grid two mix-perfume-row" style="grid-template-columns: 2fr 1.5fr auto; gap: 4px; align-items: center;">
                        <select name="mix_perfume_id[]" onchange="onPerfumeOrBottleChange(this)">
                            <option value="">-- اختر الزيت العطري --</option>
                            <?php foreach ($perfumes as $p): ?>
                                <option value="<?= e($p['id']) ?>" 
                                        data-price="<?= e($p['price_per_gram'] ?? $p['sale_price']) ?>"
                                        data-family="<?= e($p['perfume_family']) ?>"
                                        data-grade="<?= e($p['quality_grade']) ?>">
                                    <?= e($p['name']) ?> (<?= e($p['quality_grade'] ?: '-') ?>) - (<?= money($p['price_per_gram'] ?? $p['sale_price']) ?>/جم)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="display: inline-flex; align-items: center; border: 2px solid var(--primary); border-radius: 8px; overflow: hidden; background: var(--surface); box-shadow: 0 2px 8px rgba(124,58,237,0.10);">
                            <button type="button" onclick="adjustGramRow(this, -1)" style="border: none; background: var(--primary-soft); padding: 8px 14px; cursor: pointer; color: var(--primary); font-weight: 900; font-size: 18px; line-height: 1; transition: background 0.15s;">–</button>
                            <input name="mix_grams[]" type="number" step="1" min="0" placeholder="جم" value="" oninput="calculateSuggestedMixPrice()" style="width: 70px; text-align: center; border: none; background: transparent; padding: 8px 4px; -moz-appearance: textfield; margin: 0; font-size: 17px; font-weight: 800; color: var(--ink); outline: none;">
                            <button type="button" onclick="adjustGramRow(this, 1)" style="border: none; background: var(--primary-soft); padding: 8px 14px; cursor: pointer; color: var(--primary); font-weight: 900; font-size: 18px; line-height: 1; transition: background 0.15s;">+</button>
                        </div>
                        <button type="button" class="btn small danger" onclick="removeOilRow(this)" style="padding: 4px 8px; border-radius: 6px;">حذف</button>
                    </div>
                </div>
                <button type="button" class="btn primary" id="add-mix-to-cart-btn" onclick="addMixToCart()" style="grid-column: span 2; height: 36px; border-radius: 8px;">➕ إضافة التركيبة الفورية للسلة</button>
            </div>
        </div>

        <!-- Section 2: Shopping Cart (السلة) -->
        <div class="panel" style="border-top: 3px solid var(--primary);">
            <h3 style="border-bottom: 1px solid var(--line); padding-bottom: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                <span>سلة المبيعات الحالية</span>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn small danger" onclick="clearCart()" style="padding: 4px 10px; font-size: 11px; border-radius: 6px;">تفريغ السلة</button>
                    <span class="badge" id="cart-count">0 أصناف</span>
                </div>
            </h3>

            <div style="overflow-x: auto;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>اسم الصنف</th>
                            <th style="width: 110px; text-align: center;">الكمية</th>
                            <th style="width: 100px; text-align: center;">السعر</th>
                            <th style="width: 160px; text-align: center;">الخصم</th>
                            <th style="width: 100px; text-align: right;">الإجمالي</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-tbody">
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 20px;">السلة فارغة. قم باختيار صنف وإضافته بالأعلى.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Right Column: Invoice Options & Actions -->
    <div class="pos-side-panel">
        <div class="panel" style="position: sticky; top: 66px; border-top: 4px solid var(--primary);">
            <h3 style="border-bottom: 1px solid var(--line); padding-bottom: 6px; margin-bottom: 12px; font-size: 15px;">
                إغلاق الحساب والدفع</h3>

            <div style="display: grid; gap: 10px;">
                <label>موقع البيع والقناة
                    <?php if ($userLocationId !== null): ?>
                        <select name="location_id" disabled>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?= e($l['id']) ?>" selected><?= e($l['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="location_id" value="<?= e($userLocationId) ?>">
                    <?php else: ?>
                        <select name="location_id" required>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?= e($l['id']) ?>"><?= e($l['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </label>

                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="margin: 0;">العميل</label>
                        <button type="button" class="btn small primary" id="open-new-customer-modal-btn"
                            onclick="openCustomerModal()" style="padding: 2px 8px; font-size: 11px; border-radius: 6px;">+ عميل جديد</button>
                    </div>
                    <select name="customer_id" id="customer_id_select">
                        <option value="">زبون عابر</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?> - <?= e($c['phone'] ?: 'بدون هاتف') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label>طريقة الدفع
                    <select name="payment_method" id="payment_method_select" onchange="togglePaymentFields()">
                        <option value="cash">كاش (نقداً)</option>
                        <option value="instapay">إنستا باي (InstaPay)</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="vodafone_cash">فودافون كاش</option>
                        <option value="mixed_cash_instapay">مختلط (كاش + إنستا باي)</option>
                        <option value="mixed_cash_vodafone">مختلط (كاش + فودافون كاش)</option>
                    </select>
                </label>

                <!-- Split Payment Fields Container -->
                <div id="split-payment-container">
                    <label style="position: relative;">
                        <span>المبلغ كاش (ج.م)</span>
                        <span onclick="fillRemainder('cash')" style="position: absolute; left: 0; top: 0; color: var(--primary); cursor: pointer; font-size: 10px; font-weight: 700; text-decoration: underline;">⚡ باقي الحساب</span>
                        <input name="paid_cash" id="paid_cash_input" type="number" step="1" min="0" value="0" oninput="recalculateTotals()">
                    </label>
                    <label style="position: relative;">
                        <span id="secondary_payment_label">المبلغ إنستا باي (ج.م)</span>
                        <span onclick="fillRemainder('secondary')" style="position: absolute; left: 0; top: 0; color: var(--primary); cursor: pointer; font-size: 10px; font-weight: 700; text-decoration: underline;">⚡ باقي الحساب</span>
                        <input name="paid_secondary" id="paid_secondary_input" type="number" step="1" min="0" value="0" oninput="recalculateTotals()">
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 8px;">
                    <label>خصم إضافي للفاتورة
                        <select name="discount_type" id="invoice_discount_type" onchange="recalculateTotals()">
                            <option value="">بدون خصم</option>
                            <option value="amount">مبلغ ثابت</option>
                            <option value="percent">نسبة مئوية %</option>
                        </select>
                    </label>
                    <label>قيمة الخصم
                        <input name="discount_value" id="invoice_discount_value" type="number" step="1" min="0" value="0"
                            oninput="recalculateTotals()">
                    </label>
                </div>

                <label>ملاحظة الفاتورة
                    <textarea name="notes" placeholder="ملاحظات إضافية للفاتورة..."
                        style="min-height: 50px; border-radius: 8px;"></textarea>
                </label>

                <!-- Totals Section -->
                <div class="receipt-box">
                    <div class="receipt-row">
                        <span>إجمالي الأصناف:</span>
                        <strong id="lbl-subtotal">0.00 ج.م</strong>
                    </div>
                    <div class="receipt-row" style="color: var(--danger);">
                        <span>خصم الفاتورة:</span>
                        <strong id="lbl-discount">0.00 ج.م</strong>
                    </div>
                    <div class="receipt-row total">
                        <span>المطلوب دفعه:</span>
                        <strong id="lbl-total" style="color: var(--primary-dark); font-size: 17px;">0.00 ج.م</strong>
                    </div>

                    <label style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px; font-weight: 700;">المبلغ المدفوع
                        <input name="paid_total" id="paid_total_input" type="number" step="1" min="0" value="0" required
                            placeholder="0.00" oninput="recalculateTotals()" style="padding: 10px 14px; font-size: 15px; border-radius: 8px; border: 1.5px solid var(--line); font-weight: bold; text-align: center;">
                    </label>

                    <div class="receipt-row" style="border-top: 1px dashed var(--line); padding-top: 8px; margin-top: 4px;">
                        <span>متبقي (دين على العميل):</span>
                        <strong id="lbl-due" style="color: var(--danger);">0.00 ج.م</strong>
                    </div>
                    <div class="receipt-row">
                        <span>الفكة (المتبقي للعميل):</span>
                        <strong id="lbl-change" style="color: var(--success);">0.00 ج.م</strong>
                    </div>
                </div>

                <button class="btn-checkout" style="margin-top: 10px; width: 100%;">
                    <span>💵 إغلاق الفاتورة وطباعة</span>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Modal: Quick Add Customer -->
<div id="customer-modal" class="modal">
    <div class="modal-content" style="border-radius: 16px; padding: 24px; max-width: 450px;">
        <span class="close-modal" onclick="closeCustomerModal()">&times;</span>
        <h3 style="margin-top: 0; margin-bottom: 18px; color: var(--primary); font-weight: 700;">إضافة عميل جديد سريع</h3>
        <div style="display: grid; gap: 14px;">
            <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 12px; color: var(--muted);">اسم العميل الكامل<input id="new-cust-name" required placeholder="مثال: أحمد محمد علي" style="padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px;"></label>
            <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 12px; color: var(--muted);">رقم هاتف العميل<input id="new-cust-phone" type="tel" placeholder="مثال: 01012345678" style="padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px;"></label>
            <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 12px; color: var(--muted);">تاريخ الميلاد<input id="new-cust-birthdate" type="date" style="padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px;"></label>
            <button type="button" class="btn primary" id="save-customer-btn" onclick="saveQuickCustomer()" style="border-radius: 8px; padding: 12px; font-weight: 700; font-size: 14px;">حفظ العميل وتحديده</button>
            <div id="modal-error" style="color: var(--danger); font-weight: 700; font-size: 12.5px; display: none;"></div>
        </div>
    </div>
</div>

<script>
    let cart = [];

    // Prevent negative values and decimal points in all numeric inputs
    document.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            // Remove any minus sign or decimal point typed
            if (e.target.value.startsWith('-')) {
                e.target.value = e.target.value.replace('-', '');
            }
            // Remove decimal points
            if (e.target.value.includes('.') || e.target.value.includes(',')) {
                e.target.value = e.target.value.replace(/[.,]/g, '');
            }
        }
    });
    
    // Also prevent keypress of minus sign
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                e.preventDefault();
            }
        }
    });

    // Tab switching logic for adding products
    function switchAddTab(tab) {
        const tabDirectBtn = document.getElementById('tab-direct-btn');
        const tabRecipeBtn = document.getElementById('tab-recipe-btn');
        const tabMixBtn = document.getElementById('tab-mix-btn');
        const addDirectPanel = document.getElementById('add-direct-panel');
        const addRecipePanel = document.getElementById('add-recipe-panel');
        const addMixPanel = document.getElementById('add-mix-panel');

        // Reset all
        tabDirectBtn.classList.remove('active');
        tabRecipeBtn.classList.remove('active');
        tabMixBtn.classList.remove('active');
        addDirectPanel.style.display = 'none';
        addRecipePanel.style.display = 'none';
        addMixPanel.style.display = 'none';

        // Activate selected
        if (tab === 'direct') {
            tabDirectBtn.classList.add('active');
            addDirectPanel.style.display = 'grid';
        } else if (tab === 'recipe') {
            tabRecipeBtn.classList.add('active');
            addRecipePanel.style.display = 'grid';
        } else if (tab === 'mix') {
            tabMixBtn.classList.add('active');
            addMixPanel.style.display = 'grid';
        }
    }

    // Auto-fill price when product/recipe is changed
    document.getElementById('add-prod-select').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const price = option.getAttribute('data-price') || 0;
        document.getElementById('add-prod-price').value = price;
    });

    document.getElementById('add-recipe-select').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const price = option.getAttribute('data-price') || 0;
        document.getElementById('add-recipe-price').value = price;
    });

    // Add direct product to cart array
    function addProductToCart() {
        const select = document.getElementById('add-prod-select');
        const id = select.value;
        if (!id) return alert('الرجاء اختيار صنف أولاً.');

        const option = select.options[select.selectedIndex];
        const name = option.getAttribute('data-name');
        const qty = parseInt(document.getElementById('add-prod-qty').value) || 0;
        const price = parseInt(document.getElementById('add-prod-price').value) || 0;
        const discountType = document.getElementById('add-prod-discount-type').value;
        const discountValue = parseInt(document.getElementById('add-prod-discount-value').value) || 0;

        if (qty <= 0) return alert('الرجاء إدخال كمية صحيحة.');

        // Check if item exists in cart
        const existingIndex = cart.findIndex(item => item.id === id && item.type === 'product' && item.discountType === discountType && item.discountValue === discountValue);

        if (existingIndex > -1) {
            cart[existingIndex].qty += qty;
        } else {
            cart.push({
                type: 'product',
                id: id,
                name: name,
                qty: qty,
                price: price,
                discountType: discountType,
                discountValue: discountValue
            });
        }

        // Reset inputs
        select.value = '';
        const trigger = select.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
        if (trigger) trigger.textContent = '-- اختر صنف مباشر --';

        document.getElementById('add-prod-qty').value = '1';
        document.getElementById('add-prod-price').value = '0';
        document.getElementById('add-prod-discount-type').value = '';
        document.getElementById('add-prod-discount-value').value = '0';

        renderCart();
    }

    // Add recipe to cart array
    function addRecipeToCart() {
        const select = document.getElementById('add-recipe-select');
        const id = select.value;
        if (!id) return alert('الرجاء اختيار تركيبة جاهزة أولاً.');

        const option = select.options[select.selectedIndex];
        const name = option.getAttribute('data-name');
        const qty = parseInt(document.getElementById('add-recipe-qty').value) || 0;
        const price = parseInt(document.getElementById('add-recipe-price').value) || 0;

        if (qty <= 0) return alert('الرجاء إدخال كمية صحيحة.');

        const existingIndex = cart.findIndex(item => item.id === id && item.type === 'recipe');
        if (existingIndex > -1) {
            cart[existingIndex].qty += qty;
        } else {
            cart.push({
                type: 'recipe',
                id: id,
                name: name,
                qty: qty,
                price: price,
                discountType: '',
                discountValue: 0
            });
        }

        select.value = '';
        const trigger = select.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
        if (trigger) trigger.textContent = '-- اختر تركيبة جاهزة --';

        document.getElementById('add-recipe-qty').value = '1';
        document.getElementById('add-recipe-price').value = '0';

        renderCart();
    }

    // Remove item from cart array
    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    // Update cart item quantity
    function updateQty(index, val) {
        const qty = parseInt(val) || 0;
        if (qty > 0) {
            cart[index].qty = qty;
            renderCart();
        }
    }

    // Update cart item price
    function updatePrice(index, val) {
        const price = parseInt(val) || 0;
        if (price >= 0) {
            cart[index].price = price;
            renderCart();
        }
    }

    // Update cart item line discount
    function updateLineDiscountType(index, val) {
        cart[index].discountType = val;
        renderCart();
    }

    // Update cart item line discount value
    function updateLineDiscountValue(index, val) {
        cart[index].discountValue = parseInt(val) || 0;
        renderCart();
    }

    // Render cart table and populate hidden form fields
    function renderCart() {
        const tbody = document.getElementById('cart-tbody');
        const cartCount = document.getElementById('cart-count');
        const hiddenContainer = document.getElementById('hidden-cart-inputs');

        tbody.innerHTML = '';
        hiddenContainer.innerHTML = '';

        // Save to localStorage
        localStorage.setItem('pos_cart', JSON.stringify(cart));

        if (cart.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="muted" style="text-align: center; padding: 20px;">السلة فارغة. قم باختيار صنف وإضافته بالأعلى.</td></tr>`;
            cartCount.textContent = '0 أصناف';
            recalculateTotals();
            return;
        }

        cartCount.textContent = cart.length + ' أصناف';

        cart.forEach((item, index) => {
            let gross = item.qty * item.price;
            let discountText = 'بدون';
            let lineTotal = gross;

            if (item.discountType === 'percent') {
                const discAmt = gross * (item.discountValue / 100);
                discountText = `%${item.discountValue} (${discAmt} ج.م)`;
                lineTotal = gross - discAmt;
            } else if (item.discountType === 'amount') {
                discountText = `${item.discountValue} ج.م`;
                lineTotal = gross - item.discountValue;
            }

            lineTotal = Math.max(0, lineTotal);

            const badgeClass = item.type === 'product' ? 'direct' : (item.type === 'custom_recipe' ? 'custom' : 'recipe');
            const badgeLabel = item.type === 'product' ? 'جاهز' : (item.type === 'custom_recipe' ? '🧪 فورية' : 'تركيبة');

            const row = document.createElement('tr');
            row.innerHTML = `
            <td>
                <strong>${item.name}</strong> 
                <span class="badge-type ${badgeClass}">${badgeLabel}</span>
            </td>
            <td>
                <div class="qty-control">
                    <button type="button" onclick="adjustQty(${index}, -1)">–</button>
                    <input type="number" step="1" value="${item.qty}" onchange="updateQty(${index}, this.value)">
                    <button type="button" onclick="adjustQty(${index}, 1)">+</button>
                </div>
            </td>
            <td>
                <input type="number" step="1" value="${item.price}" onchange="updatePrice(${index}, this.value)" style="width: 80px; text-align: center; padding: 6px; border: 1px solid var(--line); border-radius: 6px;" ${item.type === 'recipe' ? 'readonly' : ''}>
            </td>
            <td>
                ${item.type === 'product' ? `
                    <div style="display: flex; gap: 4px; align-items: center;">
                        <select onchange="updateLineDiscountType(${index}, this.value)" style="padding: 4px 6px; font-size: 12px; border: 1px solid var(--line); border-radius: 6px;">
                            <option value="" ${item.discountType === '' ? 'selected' : ''}>بدون</option>
                            <option value="amount" ${item.discountType === 'amount' ? 'selected' : ''}>مبلغ</option>
                            <option value="percent" ${item.discountType === 'percent' ? 'selected' : ''}>%</option>
                        </select>
                        <input type="number" step="1" value="${item.discountValue}" onchange="updateLineDiscountValue(${index}, this.value)" style="width: 65px; text-align: center; padding: 4px; font-size: 12px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                ` : `<span class="muted">-</span>`}
            </td>
            <td><strong>${lineTotal} ج.م</strong></td>
            <td>
                <button type="button" class="btn-delete-item" onclick="removeItem(${index})" title="حذف">×</button>
            </td>
        `;
            tbody.appendChild(row);

            if (item.type === 'product') {
                hiddenContainer.innerHTML += `
                <input type="hidden" name="product_id[]" value="${item.id}">
                <input type="hidden" name="quantity[]" value="${item.qty}">
                <input type="hidden" name="unit_price[]" value="${item.price}">
                <input type="hidden" name="line_discount_type[]" value="${item.discountType}">
                <input type="hidden" name="line_discount_value[]" value="${item.discountValue}">
            `;
            } else if (item.type === 'recipe') {
                for (let q = 0; q < item.qty; q++) {
                    hiddenContainer.innerHTML += `
                    <input type="hidden" name="recipe_id[]" value="${item.id}">
                `;
                }
            } else if (item.type === 'custom_recipe') {
                const mixData = JSON.stringify({
                    bottle_id: item.bottle_id,
                    sale_price: item.price,
                    components: item.components
                });
                hiddenContainer.innerHTML += `
                <input type="hidden" name="mix_data[]" value='${mixData.replace(/'/g, '&#39;')}'>
            `;
            }
        });

        recalculateTotals();
    }

    // Toggle split payment inputs and manage defaults
    function togglePaymentFields() {
        const methodSelect = document.getElementById('payment_method_select');
        const splitContainer = document.getElementById('split-payment-container');
        const paidInput = document.getElementById('paid_total_input');
        const secondaryLabel = document.getElementById('secondary_payment_label');
        const secondaryInput = document.getElementById('paid_secondary_input');
        
        if (methodSelect.value === 'mixed_cash_instapay' || methodSelect.value === 'mixed_cash_vodafone') {
            splitContainer.style.display = 'grid';
            paidInput.readOnly = true;
            paidInput.style.background = 'var(--surface-soft)';
            paidInput.style.cursor = 'not-allowed';
            
            if (methodSelect.value === 'mixed_cash_instapay') {
                secondaryLabel.textContent = 'المبلغ إنستا باي (ج.م)';
                secondaryInput.setAttribute('name', 'paid_instapay');
            } else {
                secondaryLabel.textContent = 'المبلغ فودافون كاش (ج.م)';
                secondaryInput.setAttribute('name', 'paid_vodafone_cash');
            }
            
            // Auto balance to start
            const totalNeeded = parseInt(document.getElementById('lbl-total').textContent) || 0;
            document.getElementById('paid_cash_input').value = totalNeeded;
            secondaryInput.value = 0;
            paidInput.value = totalNeeded;
        } else {
            splitContainer.style.display = 'none';
            paidInput.readOnly = false;
            paidInput.style.background = 'var(--surface)';
            paidInput.style.cursor = 'auto';
            
            const totalNeeded = parseInt(document.getElementById('lbl-total').textContent) || 0;
            paidInput.value = totalNeeded;
        }
        recalculateTotals();
    }

    // Fill remaining balance to a split payment field
    function fillRemainder(field) {
        const totalNeeded = parseInt(document.getElementById('lbl-total').textContent) || 0;
        const cashInput = document.getElementById('paid_cash_input');
        const secondaryInput = document.getElementById('paid_secondary_input');
        
        let paidCash = parseInt(cashInput.value) || 0;
        let paidSecondary = parseInt(secondaryInput.value) || 0;
        
        if (field === 'cash') {
            cashInput.value = Math.max(0, totalNeeded - paidSecondary);
        } else if (field === 'secondary') {
            secondaryInput.value = Math.max(0, totalNeeded - paidCash);
        }
        recalculateTotals();
    }

    function recalculateTotals() {
        let subtotal = 0;

        // Sum cart items
        cart.forEach(item => {
            let gross = item.qty * item.price;
            let discount = 0;
            if (item.discountType === 'percent') {
                discount = gross * (item.discountValue / 100);
            } else if (item.discountType === 'amount') {
                discount = item.discountValue;
            }
            subtotal += Math.max(0, gross - discount);
        });

        // Calculate invoice discount
        const discountTypeSelect = document.getElementById('invoice_discount_type');
        const discountValueInput = document.getElementById('invoice_discount_value');
        let invDiscount = 0;
        if (discountTypeSelect.value === 'percent') {
            invDiscount = subtotal * ((parseInt(discountValueInput.value) || 0) / 100);
        } else if (discountTypeSelect.value === 'amount') {
            invDiscount = parseInt(discountValueInput.value) || 0;
        }

        let finalTotal = Math.max(0, subtotal - invDiscount);

        const paidInput = document.getElementById('paid_total_input');
        const methodSelect = document.getElementById('payment_method_select');
        
        if (methodSelect && (methodSelect.value === 'mixed_cash_instapay' || methodSelect.value === 'mixed_cash_vodafone')) {
            const cashInput = document.getElementById('paid_cash_input');
            const secondaryInput = document.getElementById('paid_secondary_input');
            
            let paidCash = parseInt(cashInput.value) || 0;
            let paidSecondary = parseInt(secondaryInput.value) || 0;
            
            paidInput.value = paidCash + paidSecondary;
        }

        let paid = parseInt(paidInput.value) || 0;
        let due = Math.max(0, finalTotal - paid);
        let change = Math.max(0, paid - finalTotal);

        // Update label displays
        document.getElementById('lbl-subtotal').textContent = subtotal + ' ج.م';
        document.getElementById('lbl-discount').textContent = invDiscount + ' ج.م';
        document.getElementById('lbl-total').textContent = finalTotal + ' ج.م';
        document.getElementById('lbl-due').textContent = due + ' ج.م';
        document.getElementById('lbl-change').textContent = change + ' ج.م';
    }

    // Adjust cart quantity with buttons (+ / -)
    function adjustQty(index, delta) {
        let current = parseInt(cart[index].qty) || 0;
        let newQty = current + delta;
        if (newQty < 1) newQty = 1;
        if (cart[index].type === 'recipe') {
            newQty = Math.max(1, Math.round(newQty));
        }
        cart[index].qty = newQty;
        renderCart();
    }

    // Adjust grams on row with buttons (+ / -)
    function adjustGramRow(btn, delta) {
        const input = btn.closest('div').querySelector('input[name="mix_grams[]"]');
        let val = parseInt(input.value) || 0;
        val += delta;
        if (val < 0) val = 0;
        input.value = val > 0 ? val : '';
        calculateSuggestedMixPrice();
    }

    // When a perfume option changes or bottle size changes
    function onPerfumeOrBottleChange(select) {
        const mixBottleSelect = document.getElementById('mix_bottle_id');
        if (mixBottleSelect && mixBottleSelect.value !== '') {
            const optionBottle = mixBottleSelect.options[mixBottleSelect.selectedIndex];
            const size = parseInt(optionBottle.getAttribute('data-size')) || 0;

            if (select.value !== '' && size > 0) {
                const row = select.closest('.mix-perfume-row');
                const gramsInput = row.querySelector('input[name="mix_grams[]"]');

                if (gramsInput.value === '' || gramsInput.value === '0') {
                    const optPerfume = select.options[select.selectedIndex];
                    const family = optPerfume.getAttribute('data-family');
                    const grade = optPerfume.getAttribute('data-grade') || '';

                    const def = formulaDefaults.find(d => 
                        parseInt(d.bottle_size_ml) === size && 
                        d.perfume_family === family && 
                        (d.quality_grade || '') === grade
                    );

                    if (def) {
                        gramsInput.value = parseInt(def.default_grams);
                    }
                }
            }
        }
        calculateSuggestedMixPrice();
    }

    // Apply default grams to all rows when bottle size changes
    function applyDefaultsToAllRows() {
        const mixBottleSelect = document.getElementById('mix_bottle_id');
        if (!mixBottleSelect || mixBottleSelect.value === '') return;

        const optionBottle = mixBottleSelect.options[mixBottleSelect.selectedIndex];
        const size = parseInt(optionBottle.getAttribute('data-size')) || 0;
        if (size <= 0) return;

        const rows = document.querySelectorAll('.mix-perfume-row');
        rows.forEach(row => {
            const perfumeSelect = row.querySelector('select[name="mix_perfume_id[]"]');
            const gramsInput = row.querySelector('input[name="mix_grams[]"]');

            if (perfumeSelect && perfumeSelect.value !== '' && (gramsInput.value === '' || gramsInput.value === '0')) {
                const optPerfume = perfumeSelect.options[perfumeSelect.selectedIndex];
                const family = optPerfume.getAttribute('data-family');
                const grade = optPerfume.getAttribute('data-grade') || '';

                const def = formulaDefaults.find(d => 
                    parseInt(d.bottle_size_ml) === size && 
                    d.perfume_family === family && 
                    (d.quality_grade || '') === grade
                );

                if (def) {
                    gramsInput.value = parseInt(def.default_grams);
                }
            }
        });
    }

    function markMixPriceManual() {
        const mixPriceInput = document.getElementById('mix_sale_price');
        if (mixPriceInput) {
            mixPriceInput.dataset.manual = '1';
        }
    }

    function resetMixPriceManual() {
        const mixPriceInput = document.getElementById('mix_sale_price');
        if (mixPriceInput) {
            mixPriceInput.dataset.manual = '0';
        }
    }

    // Calculate the suggested total price of the custom mix
    function calculateSuggestedMixPrice() {
        const mixBottleSelect = document.getElementById('mix_bottle_id');
        const mixPriceInput = document.getElementById('mix_sale_price');

        if (!mixBottleSelect || mixBottleSelect.value === '') {
            if (mixPriceInput && mixPriceInput.dataset.manual !== '1') mixPriceInput.value = '0';
            recalculateTotals();
            return;
        }

        const optionBottle = mixBottleSelect.options[mixBottleSelect.selectedIndex];
        const bottlePrice = parseInt(optionBottle.getAttribute('data-price')) || 0;

        let total = bottlePrice;

        const rows = document.querySelectorAll('.mix-perfume-row');
        rows.forEach(row => {
            const perfumeSelect = row.querySelector('select[name="mix_perfume_id[]"]');
            const gramsInput = row.querySelector('input[name="mix_grams[]"]');

            if (perfumeSelect && perfumeSelect.value !== '') {
                const optPerfume = perfumeSelect.options[perfumeSelect.selectedIndex];
                const pricePerGram = parseInt(optPerfume.getAttribute('data-price')) || 0;
                const grams = parseInt(gramsInput.value) || 0;

                total += pricePerGram * grams;
            }
        });

        if (mixPriceInput && mixPriceInput.dataset.manual !== '1') {
            mixPriceInput.value = total;
        }
        recalculateTotals();
    }

    // Add instant mix to cart array
    function addMixToCart() {
        const mixBottleSelect = document.getElementById('mix_bottle_id');
        const bottleId = mixBottleSelect.value;
        if (!bottleId) return alert('الرجاء اختيار زجاجة للتركيبة.');

        const bottleOption = mixBottleSelect.options[mixBottleSelect.selectedIndex];
        const bottleName = bottleOption.textContent.split(' - ')[0];
        const bottlePrice = parseInt(bottleOption.getAttribute('data-price')) || 0;
        const salePrice = parseInt(document.getElementById('mix_sale_price').value) || 0;
        if (salePrice <= 0) return alert('الرجاء إدخال سعر بيع صحيح للتركيبة.');

        const perfumeRows = document.querySelectorAll('.mix-perfume-row');
        let components = [];
        let hasOil = false;
        let descriptionParts = [bottleName];

        perfumeRows.forEach(row => {
            const perfumeSelect = row.querySelector('select[name="mix_perfume_id[]"]');
            const gramsInput = row.querySelector('input[name="mix_grams[]"]');
            if (perfumeSelect && perfumeSelect.value !== '') {
                const perfumeOption = perfumeSelect.options[perfumeSelect.selectedIndex];
                const perfumeId = perfumeSelect.value;
                const perfumeName = perfumeOption.textContent.split(' (')[0];
                const grams = parseInt(gramsInput.value) || 0;
                if (grams > 0) {
                    hasOil = true;
                    const family = perfumeOption.getAttribute('data-family');
                    const grade = perfumeOption.getAttribute('data-grade') || '';
                    const bottleOption = document.getElementById('mix_bottle_id').options[document.getElementById('mix_bottle_id').selectedIndex];
                    const bottleSize = parseInt(bottleOption.getAttribute('data-size')) || 0;
                    const defaultDef = formulaDefaults.find(d => 
                        parseInt(d.bottle_size_ml) === bottleSize && 
                        d.perfume_family === family && 
                        (d.quality_grade || '') === grade
                    );
                    const defaultGrams = defaultDef ? parseInt(defaultDef.default_grams) : 0;
                    
                    components.push({
                        product_id: parseInt(perfumeId),
                        name: perfumeName,
                        grams: grams,
                        default_grams: defaultGrams
                    });
                    descriptionParts.push(perfumeName + ' ' + grams + 'جم');
                }
            }
        });

        if (!hasOil) return alert('الرجاء إضافة زيت عطري واحد على الأقل مع الجرامات.');

        const mixItem = {
            type: 'custom_recipe',
            id: 'mix_' + Date.now(),
            name: '🧪 تركيبة فورية: ' + descriptionParts.join(' + '),
            qty: 1,
            price: salePrice,
            discountType: '',
            discountValue: 0,
            bottle_id: parseInt(bottleId),
            bottle_name: bottleName,
            bottle_price: bottlePrice,
            components: components
        };

        cart.push(mixItem);
        renderCart();
        resetMixBuilder();
    }

    function resetMixBuilder() {
        document.getElementById('mix_bottle_id').value = '';
        const bottleTrigger = document.getElementById('mix_bottle_id').closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
        if (bottleTrigger) bottleTrigger.textContent = '-- اختر الزجاجة --';

        document.getElementById('mix_sale_price').value = '0';
        resetMixPriceManual();

        const container = document.getElementById('mix-perfumes-container');
        const rows = container.querySelectorAll('.mix-perfume-row');
        for (let i = 1; i < rows.length; i++) {
            rows[i].remove();
        }
        const firstRow = container.querySelector('.mix-perfume-row');
        if (firstRow) {
            firstRow.querySelector('select').value = '';
            const firstRowTrigger = firstRow.querySelector('select').closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
            if (firstRowTrigger) firstRowTrigger.textContent = '-- اختر الزيت العطري --';

            firstRow.querySelector('input[name="mix_grams[]"]').value = '';
        }

        recalculateTotals();
    }

    // Clear cart manually
    function clearCart() {
        if (confirm('هل أنت متأكد من تفريغ السلة؟')) {
            cart = [];
            localStorage.removeItem('pos_cart');
            renderCart();
        }
    }

    // Instant Mix: Dynamic perfume oil rows
    function addOilRow() {
        const container = document.getElementById('mix-perfumes-container');
        const row = document.createElement('div');
        row.className = 'line-grid two mix-perfume-row';
        row.style.gridTemplateColumns = '2fr 1.5fr auto';
        row.style.gap = '6px';
        row.style.alignItems = 'center';

        row.innerHTML = `
        <select name="mix_perfume_id[]" onchange="onPerfumeOrBottleChange(this)">
            <option value="">-- اختر الزيت العطري --</option>
            <?php foreach ($perfumes as $p): ?>
                <option value="<?= e($p['id']) ?>" 
                        data-price="<?= e($p['price_per_gram'] ?? $p['sale_price']) ?>"
                        data-family="<?= e($p['perfume_family']) ?>"
                        data-grade="<?= e($p['quality_grade']) ?>">
                    <?= e($p['name']) ?> (<?= e($p['quality_grade'] ?: '-') ?>) - (<?= money($p['price_per_gram'] ?? $p['sale_price']) ?>/جم)
                </option>
            <?php endforeach; ?>
        </select>
        <div style="display: inline-flex; align-items: center; border: 2px solid var(--primary); border-radius: 8px; overflow: hidden; background: var(--surface); box-shadow: 0 2px 8px rgba(124,58,237,0.10);">
            <button type="button" onclick="adjustGramRow(this, -1)" style="border: none; background: var(--primary-soft); padding: 8px 14px; cursor: pointer; color: var(--primary); font-weight: 900; font-size: 18px; line-height: 1; transition: background 0.15s;">–</button>
            <input name="mix_grams[]" type="number" step="1" min="0" placeholder="جم" value="" oninput="calculateSuggestedMixPrice()" style="width: 70px; text-align: center; border: none; background: transparent; padding: 8px 4px; -moz-appearance: textfield; margin: 0; font-size: 17px; font-weight: 800; color: var(--ink); outline: none;">
            <button type="button" onclick="adjustGramRow(this, 1)" style="border: none; background: var(--primary-soft); padding: 8px 14px; cursor: pointer; color: var(--primary); font-weight: 900; font-size: 18px; line-height: 1; transition: background 0.15s;">+</button>
        </div>
        <button type="button" class="btn small danger" onclick="removeOilRow(this)" style="padding: 6px 10px; border-radius: 6px;">حذف</button>
    `;
        container.appendChild(row);
        makeSelectSearchable(row.querySelector('select'));
    }

    function removeOilRow(btn) {
        btn.closest('.mix-perfume-row').remove();
        calculateSuggestedMixPrice();
    }

    // Wire up bottle change event listener
    document.getElementById('mix_bottle_id').addEventListener('change', function() {
        resetMixPriceManual();
        applyDefaultsToAllRows();
        calculateSuggestedMixPrice();
    });

    // Customer Modal controls
    function openCustomerModal() {
        document.getElementById('customer-modal').classList.add('open');
        document.getElementById('new-cust-name').focus();
    }

    function closeCustomerModal() {
        document.getElementById('customer-modal').classList.remove('open');
        document.getElementById('modal-error').style.display = 'none';
        document.getElementById('new-cust-name').value = '';
        document.getElementById('new-cust-phone').value = '';
        document.getElementById('new-cust-birthdate').value = '';
    }

    // Quick Add Customer via AJAX/Fetch
    function saveQuickCustomer() {
        const name = document.getElementById('new-cust-name').value.trim();
        const phone = document.getElementById('new-cust-phone').value.trim();
        const birthdate = document.getElementById('new-cust-birthdate').value.trim();
        const errorDiv = document.getElementById('modal-error');

        if (!name) {
            errorDiv.textContent = 'الرجاء إدخال اسم العميل بالكامل.';
            errorDiv.style.display = 'block';
            return;
        }

        errorDiv.style.display = 'none';

        const params = new URLSearchParams();
        params.append('csrf', document.querySelector('input[name="csrf"]').value);
        params.append('name', name);
        params.append('phone', phone);
        if (birthdate) {
            params.append('birthdate', birthdate);
        }

        fetch('index.php?r=quick_add_customer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const select = document.getElementById('customer_id_select');
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.name + (data.phone ? ' - ' + data.phone : ' - بدون هاتف');
                    opt.selected = true;
                    select.appendChild(opt);

                    // Sync custom-select wrapper
                    const trigger = select.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
                    if (trigger) trigger.textContent = opt.textContent;

                    closeCustomerModal();
                    alert(data.message || 'تمت إضافة العميل وتحديده بنجاح.');
                } else {
                    errorDiv.textContent = data.message || 'حدث خطأ أثناء حفظ العميل.';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                console.error(err);
                errorDiv.textContent = 'حدث خطأ في الاتصال بالخادم.';
                errorDiv.style.display = 'block';
            });
    }

    // Form validation before submit
    document.getElementById('pos-main-form').addEventListener('submit', function (e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('سلة المبيعات فارغة! أضف منتجات أو تركيبات قبل إغلاق الفاتورة.');
            return;
        }

        try {
            window.open('', 'pos_print_window');
        } catch (err) {
            // ignore
        }

        sessionStorage.setItem('pos_invoice_submit_pending', '1');
    });

    if (sessionStorage.getItem('pos_invoice_submit_pending') === '1') {
        sessionStorage.removeItem('pos_invoice_submit_pending');
        if (localStorage.getItem('pos_cart')) {
            alert('لم يتم تقفيل الفاتورة، لذلك تم الاحتفاظ بالسلة كما هي. راجع رسالة الخطأ بالأعلى وحاول مرة أخرى.');
        }
    }

    // Printable invoice trigger
    (function openPrintableInvoice() {
        const params = new URLSearchParams(window.location.search);
        const inv = params.get('print_invoice');
        if (inv) {
            if (history.replaceState) {
                const cleanUrl = window.location.pathname + window.location.search.replace(/([&?])?print_invoice=[^&]*/, '').replace(/\?&/, '?').replace(/\?$/, '');
                history.replaceState({}, document.title, cleanUrl);
            }
            const url = 'index.php?r=invoice_view&id=' + encodeURIComponent(inv) + '&print=1';
            try {
                const w = window.open('', 'pos_print_window');
                if (w) {
                    w.location = url;
                    w.focus();
                } else {
                    window.open(url, '_blank');
                }
            } catch (err) {
                window.open(url, '_blank');
            }

            const clearFlag = params.get('clear_cart');
            if (clearFlag === '1') {
                try {
                    localStorage.removeItem('pos_cart');
                    cart = [];
                    renderCart();
                } catch (e) {
                    console.error('Failed to clear POS cart', e);
                }
            }
        }
    })();

    // Retrieve cart from localStorage
    try {
        const stored = localStorage.getItem('pos_cart');
        if (stored) {
            cart = JSON.parse(stored);
            renderCart();
        }
    } catch (e) {
        console.error("Error loading cart from localStorage", e);
    }

    // Initialize dropdowns
    document.addEventListener('DOMContentLoaded', () => {
        const addProdSelect = document.getElementById('add-prod-select');
        if (addProdSelect) makeSelectSearchable(addProdSelect);

        const addRecipeSelect = document.getElementById('add-recipe-select');
        if (addRecipeSelect) makeSelectSearchable(addRecipeSelect);

        const customerSelect = document.getElementById('customer_id_select');
        if (customerSelect) makeSelectSearchable(customerSelect);

        const mixBottleSelect = document.getElementById('mix_bottle_id');
        if (mixBottleSelect) makeSelectSearchable(mixBottleSelect);

        document.querySelectorAll('select[name="mix_perfume_id[]"]').forEach(sel => {
            makeSelectSearchable(sel);
        });
    });

    setTimeout(() => {
        const addProdSelect = document.getElementById('add-prod-select');
        if (addProdSelect && !addProdSelect.dataset.searchableInitialized) {
            makeSelectSearchable(addProdSelect);
            const addRecipeSelect = document.getElementById('add-recipe-select');
            if (addRecipeSelect) makeSelectSearchable(addRecipeSelect);
            const customerSelect = document.getElementById('customer_id_select');
            if (customerSelect) makeSelectSearchable(customerSelect);
            const mixBottleSelect = document.getElementById('mix_bottle_id');
            if (mixBottleSelect) makeSelectSearchable(mixBottleSelect);
            document.querySelectorAll('select[name="mix_perfume_id[]"]').forEach(sel => {
                makeSelectSearchable(sel);
            });
        }
    }, 100);
</script>

<script>
// ========== Focus-Free Barcode Scanner Logic ==========
const barcodeInput = document.getElementById('barcode-scanner-input');
const barcodeFeedback = document.getElementById('barcode-feedback');
const barcodeLookupBtn = document.getElementById('barcode-lookup-btn');

// Automatically focus barcode input when clicking anywhere on the page
document.addEventListener('click', function(e) {
    const isInteractive = e.target.closest('input, select, textarea, button, a, [role="button"], .custom-select-trigger, .custom-select-options-box, .custom-select-item, .close-modal, .modal-content');
    if (!isInteractive) {
        if (barcodeInput) {
            barcodeInput.focus();
        }
    }
});

// Capture keystrokes from physical scanner globally in the background
let barcodeBuffer = '';
let lastKeyTime = Date.now();

document.addEventListener('keydown', function(e) {
    const activeEl = document.activeElement;
    const isInputField = activeEl && (
        activeEl.tagName === 'INPUT' ||
        activeEl.tagName === 'TEXTAREA' ||
        activeEl.tagName === 'SELECT' ||
        activeEl.isContentEditable
    );
    
    if (!isInputField) {
        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 200) {
            barcodeBuffer = '';
        }
        lastKeyTime = currentTime;

        if (e.key.length === 1) {
            barcodeBuffer += e.key;
        } else if (e.key === 'Enter') {
            if (barcodeBuffer.trim().length > 0) {
                e.preventDefault();
                const barcode = barcodeBuffer.trim();
                barcodeBuffer = '';
                lookupBarcode(barcode);
            }
        }
    }
});

function lookupBarcode(barcode) {
    barcode = barcode.trim();
    if (!barcode) {
        barcodeFeedback.textContent = '❌ أدخل باركود';
        barcodeFeedback.style.color = 'var(--danger)';
        return;
    }
    
    barcodeFeedback.textContent = '⏳ جاري البحث...';
    barcodeFeedback.style.color = 'var(--muted)';
    
    fetch('index.php?r=barcode_lookup&barcode=' + encodeURIComponent(barcode))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.product) {
                const p = data.product;
                barcodeFeedback.textContent = '✅ ' + p.name;
                barcodeFeedback.style.color = 'var(--success)';
                
                if (p.type === 'bottle') {
                    const mixBottleSelect = document.getElementById('mix_bottle_id');
                    if (mixBottleSelect) {
                        for (let i = 0; i < mixBottleSelect.options.length; i++) {
                            if (mixBottleSelect.options[i].value == p.id) {
                                mixBottleSelect.selectedIndex = i;
                                mixBottleSelect.dispatchEvent(new Event('change'));
                                const trigger = mixBottleSelect.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
                                if (trigger) trigger.textContent = mixBottleSelect.options[i].textContent;
                                break;
                            }
                        }
                    }
                    barcodeFeedback.textContent = '✅ تم تحديد الزجاجة: ' + p.name;
                } else if (p.type === 'perfume_gram') {
                    barcodeFeedback.textContent = '✅ تم التعرف: ' + p.name + ' - أضفها في تركيبة فورية';
                    
                    const rows = document.querySelectorAll('.mix-perfume-row');
                    let targetRow = null;
                    for (let row of rows) {
                        const sel = row.querySelector('select[name="mix_perfume_id[]"]');
                        if (sel && sel.value === '') {
                            targetRow = row;
                            break;
                        }
                    }
                    if (!targetRow) {
                        addOilRow();
                        const newRows = document.querySelectorAll('.mix-perfume-row');
                        targetRow = newRows[newRows.length - 1];
                    }
                    
                    const sel = targetRow.querySelector('select[name="mix_perfume_id[]"]');
                    if (sel) {
                        for (let i = 0; i < sel.options.length; i++) {
                            if (sel.options[i].value == p.id) {
                                sel.selectedIndex = i;
                                sel.dispatchEvent(new Event('change'));
                                const trigger = sel.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
                                if (trigger) trigger.textContent = sel.options[i].textContent;
                                break;
                            }
                        }
                    }
                } else if (p.type === 'recipe' || p.type === 'fixed') {
                    const prodSelect = document.getElementById('add-prod-select');
                    let found = false;
                    for (let i = 0; i < prodSelect.options.length; i++) {
                        if (prodSelect.options[i].value == p.id) {
                            prodSelect.selectedIndex = i;
                            prodSelect.dispatchEvent(new Event('change'));
                            const trigger = prodSelect.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
                            if (trigger) trigger.textContent = prodSelect.options[i].textContent;
                            
                            document.getElementById('add-prod-qty').value = '1';
                            setTimeout(() => addProductToCart(), 100);
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        const recipeSelect = document.getElementById('add-recipe-select');
                        for (let i = 0; i < recipeSelect.options.length; i++) {
                            if (recipeSelect.options[i].value == p.id) {
                                switchAddTab('recipe');
                                recipeSelect.selectedIndex = i;
                                recipeSelect.dispatchEvent(new Event('change'));
                                const trigger = recipeSelect.closest('.custom-select-wrapper')?.querySelector('.custom-select-trigger');
                                if (trigger) trigger.textContent = recipeSelect.options[i].textContent;
                                
                                document.getElementById('add-recipe-qty').value = '1';
                                setTimeout(() => addRecipeToCart(), 100);
                                break;
                            }
                        }
                    }
                }
                
                barcodeInput.value = '';
                barcodeInput.focus();
            } else {
                barcodeFeedback.textContent = '❌ ' + (data.message || 'غير موجود');
                barcodeFeedback.style.color = 'var(--danger)';
                barcodeInput.style.borderColor = 'var(--danger)';
                setTimeout(() => {
                    barcodeInput.style.borderColor = 'var(--line)';
                    barcodeInput.focus();
                    barcodeInput.select();
                }, 300);
            }
        }
        .catch(err => {
            console.error(err);
            barcodeFeedback.textContent = '❌ خطأ في الاتصال';
            barcodeFeedback.style.color = 'var(--danger)';
        });
}

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        lookupBarcode(this.value);
    }
});

barcodeLookupBtn.addEventListener('click', function() {
    lookupBarcode(barcodeInput.value);
});

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if(barcodeInput) barcodeInput.focus();
    }, 300);
});
</script>
