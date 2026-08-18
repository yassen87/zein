<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_new_product_title');

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$brandIdFromUrl = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
$catFromUrl = isset($_GET['cat']) ? trim((string)$_GET['cat']) : '';

$flashError = $_SESSION['product_flash_error'] ?? '';
$flashData = $_SESSION['product_flash_data'] ?? [];
if ($flashError !== '') {
    unset($_SESSION['product_flash_error'], $_SESSION['product_flash_data']);
}

// Detect whether this product should use flat price (offers/brands = no variants)
$isFlatPrice = false;

$cats = [];
$brands = [];
if ($pdo !== null) {
    try {
        $cats = $pdo->query('SELECT slug, name_en, name_ar FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {
        $cats = [];
    }
}
if ($cats === []) {
    $cats = [['slug' => 'unisex', 'name_en' => 'Unisex', 'name_ar' => 'للجنسين']];
}

// Load brands for dropdown
$brands = [];
if ($pdo !== null) {
    try {
        $brands = $pdo->query('SELECT id, name_en, name_ar FROM brands ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {}
}

// Flat-price categories (no variants)
$flatCatSlugs = ['offers', 'brands'];

$product = [
    'id' => 0,
    'slug' => '',
    'brand_id' => null,
    'category' => 'unisex',
    'categories' => ['unisex'],
    'season' => 'both',
    'is_bestseller' => false,
    'active' => true,
    'name_en' => '',
    'name_ar' => '',
    'notes_en' => '',
    'notes_ar' => '',
    'description_en' => 'Premium fragrance.',
    'description_ar' => 'عطر فاخر ومميز.',
    'primary_image_key' => 'default',
    'sort_order' => 0,
];

if (!empty($flashData) && $id === 0) {
    $product['name_en'] = trim((string)($flashData['name_en'] ?? $product['name_en']));
    $product['name_ar'] = trim((string)($flashData['name_ar'] ?? $product['name_ar']));
    $product['notes_en'] = trim((string)($flashData['notes_en'] ?? $product['notes_en']));
    $product['notes_ar'] = trim((string)($flashData['notes_ar'] ?? $product['notes_ar']));
    $product['description_en'] = trim((string)($flashData['description_en'] ?? $product['description_en']));
    $product['description_ar'] = trim((string)($flashData['description_ar'] ?? $product['description_ar']));
    $product['primary_image_key'] = trim((string)($flashData['primary_image_key'] ?? $product['primary_image_key']));
    if (isset($flashData['categories']) && is_array($flashData['categories'])) {
        $product['categories'] = array_values(array_filter(array_map('strval', $flashData['categories']), fn($s) => $s !== ''));
        if (!empty($product['categories'])) {
            $product['category'] = $product['categories'][0];
        }
    }
}

$variants = [];

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row !== false) {
        $product = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
'brand_id' => isset($row['brand_id']) ? (int)$row['brand_id'] : null,
            'category' => (string) $row['category'],
            'categories' => [(string) $row['category']],
            'season' => (string) $row['season'],
            'is_bestseller' => !empty($row['is_bestseller']),
            'active' => !empty($row['active']),
            'name_en' => (string) $row['name_en'],
            'name_ar' => (string) $row['name_ar'],
            'notes_en' => (string) ($row['notes_en'] ?? ''),
            'notes_ar' => (string) ($row['notes_ar'] ?? ''),
            'description_en' => (string) $row['description_en'],
            'description_ar' => (string) $row['description_ar'],
            'primary_image_key' => (string) $row['primary_image_key'],
            'sort_order' => (int) $row['sort_order'],
        ];
        // Load multi-categories from pivot table
        try {
            $cst = $pdo->prepare('SELECT category_slug FROM product_categories WHERE product_id = ?');
            $cst->execute([$id]);
            $pivotCats = $cst->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($pivotCats)) $product['categories'] = $pivotCats;
        } catch (Throwable) {}

        $vst = $pdo->prepare('SELECT label_en, label_ar, price, compare_at_price, stock, sort_order FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $vst->execute([$id]);
        $variants = $vst->fetchAll();
    }
}

// Pre-select category from URL
if ($id === 0 && $catFromUrl !== '') {
    $product['categories'] = [$catFromUrl];
    $product['category'] = $catFromUrl;
}

// Detect flat-price mode from primary category
$isFlatPrice = in_array($product['category'], $flatCatSlugs, true);

// Default variant if none exists and NOT flat-price
if (empty($variants) && !$isFlatPrice) {
    $variants[] = ['label_en' => '100 ml', 'label_ar' => '100 مل', 'price' => '', 'compare_at_price' => '', 'stock' => 10, 'sort_order' => 0];
}

// Extract flat price from first variant for offer/brand products
$flatPrice = '';
$flatCompare = '';
$flatStock = 50;
if ($isFlatPrice && !empty($variants)) {
    $flatPrice = (string) $variants[0]['price'];
    $flatCompare = (string) ($variants[0]['compare_at_price'] ?? '');
    $flatStock = (int) ($variants[0]['stock'] ?? 50);
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="margin-bottom:2rem; text-align:center;">
    <h1 style="font-size:1.8rem; color:#111;">
        <?php if ($id > 0): ?>
            تعديل المنتج
        <?php elseif (in_array('offers', $product['categories'], true)): ?>
            إضافة عرض جديد
        <?php elseif (in_array('brands', $product['categories'], true)): ?>
            إضافة منتج ماركة عالمية
        <?php else: ?>
            إضافة منتج جديد
        <?php endif; ?>
    </h1>
    <p style="color:#888;">
        <?php if ($isFlatPrice && $id === 0): ?>
            <?= $product['category'] === 'offers' ? 'أضف عرضاً خاصاً بدون أحجام — سعر واحد فقط' : 'أضف منتج ماركة عالمية بدون أحجام — سعر واحد فقط' ?>
        <?php else: ?>
            أدخل تفاصيل المنتج أدناه
        <?php endif; ?>
    </p>
</div>

<?php if ($flashError !== ''): ?>
<div style="background:#fff5f5; border:1px solid #fecaca; color:#b91c1c; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; font-weight:600; text-align:center;">
    ⚠️ <?= esc($flashError) ?>
</div>
<?php endif; ?>

<div style="max-width: 900px; margin: 0 auto;">
    <form class="admin-form" method="post" action="<?= esc(admin_url('product_save.php')) ?>" id="product-form" novalidate onsubmit="return handleFormSubmit()">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
        
        <input type="hidden" name="is_brand_product" id="is_brand_product_input" value="<?= $isFlatPrice && in_array('brands', $product['categories'], true) ? '1' : '0' ?>">
        <input type="hidden" name="brand_id" id="brand_id_input" value="<?= $brandIdFromUrl ?? (int)($product['brand_id'] ?? 0) ?>">
        <input type="hidden" name="slug" value="<?= esc($product['slug']) ?>">
        <input type="hidden" name="season" value="both">
        <input type="hidden" name="active" value="1">
        <input type="hidden" name="sort_order" value="<?= (int) $product['sort_order'] ?>">

        <div class="admin-card" style="padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            
            <!-- القسم الأساسي (اختيار واحد) -->
            <div style="margin-bottom:2rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">
                <label style="font-weight:600; margin-bottom:0.75rem; display:block;">قسم المنتج</label>
                <div style="display:flex; flex-wrap:wrap; gap:0.6rem;">
                    <?php foreach ($cats as $c):
                        if ($c['slug'] === 'offers' || $c['slug'] === 'brands') continue;
                        $checked = in_array($c['slug'], $product['categories'], true) ? ' checked' : '';
                        $isFlat = in_array($c['slug'], $flatCatSlugs, true);
                    ?>
                    <label class="cat-checkbox cat-<?= $c['slug'] ?> <?= $isFlat ? 'cat-flat' : '' ?>" style="display:flex; align-items:center; gap:0.4rem; background:#f9f9f9; border:1px solid #ddd; border-radius:8px; padding:0.45rem 0.9rem; cursor:pointer; transition:all 0.2s;">
                        <input type="checkbox" name="categories[]" value="<?= esc((string)$c['slug']) ?>"<?= $checked ?> style="accent-color:#c5a059; width:16px; height:16px;" data-flat="<?= $isFlat ? '1' : '0' ?>">
                        <?= esc((string)($c['name_en'] ?? $c['slug'])) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Brand Selector (visible only when brands category is selected) -->
            <div id="brand-selector-section" style="margin-bottom:2rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem; <?= in_array('brands', $product['categories'], true) ? '' : 'display:none;' ?>">
                <label style="font-weight:600; margin-bottom:0.75rem; display:block;">اختر الماركة</label>
                <select name="brand_id_selector" id="brand_id_selector" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px; font-size:1rem;">
                    <option value="">-- اختر الماركة --</option>
                    <?php foreach ($brands as $b): 
                        $sel = ((int)$b['id'] === ($brandIdFromUrl ?? (int)($product['brand_id'] ?? 0))) ? ' selected' : '';
                    ?>
                    <option value="<?= (int)$b['id'] ?>"<?= $sel ?>><?= esc((string)($b['name_en'] ?? $b['name_ar'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="color:#888; font-size:0.78rem; margin:0.5rem 0 0;">اختيار الماركة يربط المنتج بالماركة العالمية المحددة</p>
            </div>

            <!-- علامات التمييز -->
            <div style="margin-bottom:2rem; display:flex; gap:2rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-weight:600;">
                    <input type="checkbox" name="is_bestseller" value="1" <?= $product['is_bestseller'] ? 'checked' : '' ?> style="accent-color:#c5a059; width:18px; height:18px;">
                    تمييز كأكثر مبيعاً (Bestseller)
                </label>
            </div>

            <!-- الاسم والوصف -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">اسم المنتج (إنجليزي)</label>
                    <input type="text" name="name_en" required value="<?= esc($product['name_en']) ?>" placeholder="e.g. Sauvage Elixir" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px;">
                </div>
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">اسم المنتج (عربي)</label>
                    <input type="text" name="name_ar" required value="<?= esc($product['name_ar']) ?>" dir="rtl" placeholder="مثلاً: سواج إلكسير" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">الوصف (EN)</label>
                    <textarea name="description_en" rows="2" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px;"><?= esc($product['description_en']) ?></textarea>
                </div>
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">الوصف (AR)</label>
                    <textarea name="description_ar" rows="2" dir="rtl" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px;"><?= esc($product['description_ar']) ?></textarea>
                </div>
            </div>

            <!-- الصورة -->
            <div style="margin-bottom:2rem;">
                <label style="font-weight:600; margin-bottom:1rem; display:block;">صورة المنتج</label>
                <div style="border: 2px dashed #c5a059; border-radius: 15px; padding: 2rem; text-align: center; background: #fff;">
                    <div id="image-preview" style="margin-bottom:1rem; <?= $product['primary_image_key'] !== 'default' ? '' : 'display:none;' ?>">
                        <img src="<?= esc(str_starts_with($product['primary_image_key'], 'http') ? $product['primary_image_key'] : '') ?>" style="max-width:200px; max-height:200px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1);">
                    </div>
                    <input type="text" id="primary_image_key" name="primary_image_key" value="<?= esc($product['primary_image_key']) ?>" style="display:none;">
                    <button type="button" id="btn-upload-trigger" style="background:#c5a059; color:white; border:none; padding:1rem 2rem; border-radius:50px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:0.5rem; margin:0 auto;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        اختر صورة من جهازك
                    </button>
                    <input type="file" id="image-upload-input" style="display:none;" accept="image/*">
                </div>
            </div>

            <!-- Flat price section (for offers/brands) -->
            <div id="flat-price-section" style="margin-bottom:2rem; <?= $isFlatPrice ? '' : 'display:none;' ?>">
                <div style="background:#fefce8; border:1px solid #fde68a; border-radius:12px; padding:1.5rem;">
                    <label style="font-weight:600; margin-bottom:1rem; display:block; color:#92400e;">
                        💰 سعر واحد — بدون أحجام
                    </label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.5rem;">
                        <div>
                            <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">السعر (ج.م)</label>
                            <input type="text" name="price" id="flat-price" value="<?= esc($flatPrice) ?>" placeholder="0.00" style="width:100%; padding:0.8rem; border:1px solid #fde68a; border-radius:10px; background:#fff;">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">السعر قبل الخصم (اختياري)</label>
                            <input type="text" name="compare_at_price" value="<?= esc($flatCompare) ?>" placeholder="0.00" style="width:100%; padding:0.8rem; border:1px solid #fde68a; border-radius:10px; background:#fff;">
                        </div>
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                                <label style="font-size:0.85rem; color:#666; margin:0;">المخزون</label>
                                <label style="font-size:0.75rem; color:#059669; font-weight:bold; cursor:pointer; display:flex; align-items:center; gap:4px;">
                                    <input type="checkbox" name="flat_unlimited_stock" id="flat_unlimited_stock" value="1" <?= $flatStock < 0 ? 'checked' : '' ?> onchange="toggleFlatUnlimited(this)">
                                    <span>♾️ غير محدود</span>
                                </label>
                            </div>
                            <input type="number" name="stock" id="flat_stock_input" value="<?= $flatStock < 0 ? -1 : $flatStock ?>" style="width:100%; padding:0.8rem; border:1px solid #fde68a; border-radius:10px; background:#fff; <?= $flatStock < 0 ? 'opacity:0.6;' : '' ?>" <?= $flatStock < 0 ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variants section (hidden for offers/brands) -->
            <div id="variants-section" style="<?= $isFlatPrice ? 'display:none;' : '' ?>">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <label style="font-weight:600;">الأحجام والأسعار</label>
                    <button type="button" id="add-variant" class="btn-admin" style="padding:0.4rem 1rem; font-size:0.85rem; background:#eee; color:#333;">+ إضافة حجم آخر</button>
                </div>
                
                <div id="variants-container">
                    <?php foreach ($variants as $idx => $v): ?>
                        <div class="variant-row" style="display:grid; grid-template-columns: 1.2fr 1fr 1fr auto; gap:1rem; margin-bottom:1rem; background:#f9f9f9; padding:1.2rem; border-radius:12px; align-items: end;">
                            <div>
                                <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">الحجم (مثلاً: 100 مل)</label>
                                <input type="text" name="variants[<?= $idx ?>][label_ar]" value="<?= esc($v['label_ar']) ?>" dir="rtl" placeholder="100 مل" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px;">
                                <input type="hidden" name="variants[<?= $idx ?>][label_en]" value="<?= esc($v['label_en']) ?>">
                            </div>
                            <div>
                                <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">السعر (ج.م)</label>
                                <input type="text" name="variants[<?= $idx ?>][price]" value="<?= esc($v['price']) ?>" placeholder="0.00" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px;">
                            </div>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                                    <label style="font-size:0.85rem; color:#666; margin:0;">المخزون</label>
                                    <label style="font-size:0.72rem; color:#059669; font-weight:bold; cursor:pointer; display:flex; align-items:center; gap:3px;">
                                        <input type="checkbox" name="variants[<?= $idx ?>][is_unlimited]" value="1" <?= (int)$v['stock'] < 0 ? 'checked' : '' ?> onchange="toggleVariantUnlimited(this)">
                                        <span>♾️ غير محدود</span>
                                    </label>
                                </div>
                                <input type="number" name="variants[<?= $idx ?>][stock]" value="<?= (int) $v['stock'] < 0 ? -1 : (int) $v['stock'] ?>" class="variant-stock-input" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px; <?= (int)$v['stock'] < 0 ? 'opacity:0.6;' : '' ?>" <?= (int)$v['stock'] < 0 ? 'readonly' : '' ?>>
                            </div>
                            <input type="hidden" name="variants[<?= $idx ?>][sort_order]" value="<?= (int) $v['sort_order'] ?>">
                            <?php if ($idx > 0): ?>
                                <button type="button" class="remove-variant" style="background:#ff4757; color:white; border:none; width:35px; height:35px; border-radius:8px; cursor:pointer; margin-bottom: 0.1rem;">×</button>
                            <?php else: ?>
                                <div style="width:35px;"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
            <!-- خيار إرسال إشعار للعملاء بالواتساب -->
            <div style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 1.5px solid #a7f3d0; border-radius: 16px; padding: 1.25rem 1.5rem; margin-top: 2rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(16,185,129,0.08);">
                <label style="display:flex; align-items:center; gap:1rem; cursor:pointer; width:100%;">
                    <input type="checkbox" name="broadcast_to_whatsapp" value="1" style="accent-color:#10b981; width:22px; height:22px; cursor:pointer; flex-shrink:0;">
                    <div>
                        <span style="font-weight:800; font-size:0.95rem; color:#065f46; display:flex; align-items:center; gap:6px;">
                            <span>📢</span> إرسال إشعار فوري لجميع العملاء على الواتساب بهذا العطر
                        </span>
                        <p style="color:#047857; font-size:0.8rem; margin:0.3rem 0 0; line-height:1.4;">
                            يقوم البوت تلقائياً بإرسال رسالة ترويجية جذابة بالاسم والسعر والرابط المباشر إلى جميع عملاء المتجر.
                        </p>
                    </div>
                </label>
            </div>

            <div style="text-align:center; margin-top:2rem;">
                <button type="submit" style="background:#111; color:white; padding:1.2rem 5rem; font-size:1.1rem; border-radius:50px; cursor:pointer; border:none; width:100%; font-weight:600;">حفظ ونشر المنتج</button>
                <a href="<?= esc(admin_url('products.php')) ?>" style="display:block; margin-top:1rem; color:#888; text-decoration:none;">إلغاء والعودة</a>
            </div>

        </div>
    </form>
</div>

<script>
function handleFormSubmit() {
    var submitBtn = document.querySelector('#product-form button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري الحفظ...';
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const uploadInput = document.getElementById('image-upload-input');
    const uploadBtn = document.getElementById('btn-upload-trigger');
    const previewContainer = document.getElementById('image-preview');
    const addVariantBtn = document.getElementById('add-variant');
    const variantsContainer = document.getElementById('variants-container');
    const variantsSection = document.getElementById('variants-section');
    const flatPriceSection = document.getElementById('flat-price-section');

    // Sync brand dropdown to hidden input (outside checkbox handler)
    var brandSelector = document.getElementById('brand_id_selector');
    var brandInput = document.getElementById('brand_id_input');
    if (brandSelector && brandInput) {
        brandSelector.addEventListener('change', function() {
            brandInput.value = this.value;
        });
    }

    // Category toggle: flat price vs variants
    var flatSlugs = <?= json_encode($flatCatSlugs) ?>;
    document.querySelectorAll('input[name="categories[]"]').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var checked = document.querySelectorAll('input[name="categories[]"]:checked');
            var hasFlat = false;
            checked.forEach(function(c) {
                if (flatSlugs.indexOf(c.value) !== -1) hasFlat = true;
            });
            if (hasFlat) {
                // Uncheck all non-flat categories
                checked.forEach(function(c) {
                    if (flatSlugs.indexOf(c.value) === -1) c.checked = false;
                });
                variantsSection.style.display = 'none';
                flatPriceSection.style.display = 'block';
            } else {
                variantsSection.style.display = 'block';
                flatPriceSection.style.display = 'none';
            }

            // Show/hide brand selector
            var brandSection = document.getElementById('brand-selector-section');
            var brandInput = document.getElementById('brand_id_input');
            var isBrandProductInput = document.getElementById('is_brand_product_input');
            var brandSelector = document.getElementById('brand_id_selector');

            if (hasFlat) {
                var hasBrands = false;
                checked.forEach(function(c) { if (c.value === 'brands') hasBrands = true; });
                if (brandSection) brandSection.style.display = hasBrands ? 'block' : 'none';
                if (isBrandProductInput) isBrandProductInput.value = hasBrands ? '1' : '0';
            } else {
                if (brandSection) brandSection.style.display = 'none';
                if (isBrandProductInput) isBrandProductInput.value = '0';
                if (brandInput) brandInput.value = '';
            }
        });
    });

    // Add new variant row
    let variantCount = <?= count($variants) ?>;
    if (addVariantBtn) {
    addVariantBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.style = 'display:grid; grid-template-columns: 1.2fr 1fr 1fr auto; gap:1rem; margin-bottom:1rem; background:#f9f9f9; padding:1.2rem; border-radius:12px; align-items: end;';
        row.innerHTML = `
            <div>
                <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">الحجم (مثلاً: 50 مل)</label>
                <input type="text" name="variants[${variantCount}][label_ar]" dir="rtl" placeholder="50 مل" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px;">
                <input type="hidden" name="variants[${variantCount}][label_en]" value="">
            </div>
            <div>
                <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">السعر (ج.م)</label>
                <input type="text" name="variants[${variantCount}][price]" placeholder="0.00" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                    <label style="font-size:0.85rem; color:#666; margin:0;">المخزون</label>
                    <label style="font-size:0.72rem; color:#059669; font-weight:bold; cursor:pointer; display:flex; align-items:center; gap:3px;">
                        <input type="checkbox" name="variants[${variantCount}][is_unlimited]" value="1" onchange="toggleVariantUnlimited(this)">
                        <span>♾️ غير محدود</span>
                    </label>
                </div>
                <input type="number" name="variants[${variantCount}][stock]" value="10" class="variant-stock-input" style="width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <input type="hidden" name="variants[${variantCount}][sort_order]" value="${variantCount}">
            <button type="button" class="remove-variant" style="background:#ff4757; color:white; border:none; width:35px; height:35px; border-radius:8px; cursor:pointer; margin-bottom: 0.1rem;">×</button>
        `;
        variantsContainer.appendChild(row);
        
        const arInput = row.querySelector('input[dir="rtl"]');
        const enHidden = row.querySelector('input[type="hidden"]');
        arInput.addEventListener('input', () => enHidden.value = arInput.value);

        variantCount++;
    });
    }

    window.toggleFlatUnlimited = function(chk) {
        var input = document.getElementById('flat_stock_input');
        if (chk.checked) {
            input.value = -1;
            input.readOnly = true;
            input.style.opacity = '0.6';
        } else {
            input.value = 50;
            input.readOnly = false;
            input.style.opacity = '1';
        }
    };

    window.toggleVariantUnlimited = function(chk) {
        var row = chk.closest('.variant-row');
        var input = row.querySelector('.variant-stock-input');
        if (chk.checked) {
            input.value = -1;
            input.readOnly = true;
            input.style.opacity = '0.6';
        } else {
            input.value = 10;
            input.readOnly = false;
            input.style.opacity = '1';
        }
    };

    if (variantsContainer) {
    variantsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-variant')) {
            e.target.closest('.variant-row').remove();
        }
    });
    }

    // Image Upload Logic
    if (uploadBtn && uploadInput) {
    uploadBtn.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        const file = this.files[0];
        const formData = new FormData();
        formData.append('image', file);
        const originalHtml = uploadBtn.innerHTML;
        uploadBtn.innerHTML = 'جاري الرفع...';
        uploadBtn.disabled = true;
        fetch('upload_handler.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            if (data.success) {
                const filename = data.filename;
                const url = data.url;
                document.getElementById('primary_image_key').value = filename;
                previewContainer.style.display = 'block';
                previewContainer.querySelector('img').src = url;
            } else { alert(data.error || 'فشل الرفع'); }
        }).catch(err => {
            console.error(err);
            alert('حدث خطأ أثناء الرفع');
        }).finally(() => {
            uploadBtn.innerHTML = originalHtml;
            uploadBtn.disabled = false;
        });
    });
    }
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
