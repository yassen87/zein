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

// Load Categories
$cats = [];
if ($pdo !== null) {
    try {
        $cats = $pdo->query('SELECT slug, name_en, name_ar FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {
        $cats = [];
    }
}
if ($cats === []) {
    $cats = [
        ['slug' => 'unisex', 'name_en' => 'Unisex', 'name_ar' => 'للجنسين'],
        ['slug' => 'men', 'name_en' => 'Men', 'name_ar' => 'رجالي'],
        ['slug' => 'women', 'name_en' => 'Women', 'name_ar' => 'نسائي'],
        ['slug' => 'niche', 'name_en' => 'Niche', 'name_ar' => 'نيش'],
        ['slug' => 'designer', 'name_en' => 'Designer', 'name_ar' => 'ماركات عالمية']
    ];
}

// Load Brands
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
            if (!empty($pivotCats)) {
                $product['categories'] = $pivotCats;
            }
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
    $variants[] = [
        'label_en' => '100 ml',
        'label_ar' => '100 مل',
        'price' => '',
        'compare_at_price' => '',
        'stock' => -1, // Default to Unlimited
        'sort_order' => 0
    ];
}

// Extract flat price from first variant
$flatPrice = '';
$flatCompare = '';
$flatStock = -1;
if ($isFlatPrice && !empty($variants)) {
    $flatPrice = (string) $variants[0]['price'];
    $flatCompare = (string) ($variants[0]['compare_at_price'] ?? '');
    $flatStock = (int) ($variants[0]['stock'] ?? -1);
}

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Luxury Product Editor Design - Pure Vanilla CSS */
.prod-edit-wrap {
    max-width: 1200px;
    margin: 0 auto 3rem auto;
    padding: 1rem;
    box-sizing: border-box;
    font-family: inherit;
}
.pe-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: 1.5px solid rgba(212, 175, 55, 0.4);
    border-radius: 20px;
    padding: 1.75rem 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
}
.pe-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    margin: 0 0 0.35rem 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.pe-badge-id {
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid #d4af37;
    color: #f59e0b;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 800;
}
.pe-badge-status {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid #10b981;
    color: #34d399;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 700;
}
.pe-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.pe-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1.5px solid #f1f5f9;
}
.pe-card-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.pe-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.pe-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.pe-field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.pe-label {
    font-size: 0.85rem;
    font-weight: 800;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pe-input, .pe-textarea, .pe-select {
    width: 100%;
    box-sizing: border-box;
    padding: 0.85rem 1.1rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 0.92rem;
    background: #ffffff;
    color: #0f172a;
    transition: all 0.2s ease;
    font-family: inherit;
}
.pe-input:focus, .pe-textarea:focus, .pe-select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
    background: #ffffff;
}
.pe-cat-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.pe-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.15rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 50px;
    background: #f8fafc;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.pe-cat-pill:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.pe-cat-pill.is-active {
    background: #0f172a;
    border-color: #0f172a;
    color: #f8fafc;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
}
.pe-cat-pill input[type="checkbox"] {
    accent-color: #d4af37;
    width: 17px;
    height: 17px;
    cursor: pointer;
}
/* Variant Row styling */
.pe-variant-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr auto;
    gap: 1.25rem;
    align-items: end;
    transition: all 0.2s;
}
.pe-variant-card:hover {
    border-color: #cbd5e1;
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}
/* Unlimited Toggle Switch */
.unlimited-toggle-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 0.25rem 0.6rem;
    border-radius: 50px;
    font-size: 0.72rem;
    font-weight: 800;
    color: #047857;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s;
}
.unlimited-toggle-wrap:hover {
    background: #d1fae5;
}
.unlimited-toggle-wrap input[type="checkbox"] {
    accent-color: #059669;
    width: 15px;
    height: 15px;
    cursor: pointer;
}
.unlimited-badge-active {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    background: #ecfdf5;
    border: 1.5px solid #6ee7b7;
    border-radius: 12px;
    color: #047857;
    font-weight: 900;
    font-size: 0.85rem;
    gap: 6px;
}
.btn-gold-action {
    background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
    color: #ffffff;
    padding: 1rem 2.5rem;
    border-radius: 14px;
    font-size: 1.05rem;
    font-weight: 800;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
    transition: all 0.25s ease;
    text-decoration: none;
}
.btn-gold-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.45);
    color: #ffffff;
}
.btn-add-var {
    background: #ffffff;
    border: 1.5px dashed #cbd5e1;
    color: #0f172a;
    padding: 0.65rem 1.25rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-add-var:hover {
    border-color: #d4af37;
    color: #b45309;
    background: #fffbeb;
}
.btn-del-var {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-del-var:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

@media (max-width: 768px) {
    .pe-grid-2, .pe-grid-3 {
        grid-template-columns: 1fr;
    }
    .pe-variant-card {
        grid-template-columns: 1fr;
    }
    .pe-hero {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="prod-edit-wrap">

    <!-- Top Hero Header -->
    <div class="pe-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                <span class="pe-badge-id">ID #<?= (int)$product['id'] ?></span>
                <span class="pe-badge-status"><?= $id > 0 ? '✓ منتج نشط في المتجر' : '✦ إضافة عطر جديد' ?></span>
            </div>
            <h1 class="pe-hero-title">
                <span>👑</span> <?= $id > 0 ? 'تعديل تفاصيل العطر: ' . esc($product['name_ar'] ?: $product['name_en']) : 'إضافة عطر جديد إلى متجر زين' ?>
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.4;">
                تحكم بكافة تفاصيل العطر، الأحجام والأسعار، المخزون غير المحدود، ومعرض الصور بجودة عالية.
            </p>
        </div>

        <div>
            <a href="<?= esc(admin_url('products.php')) ?>" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#ffffff; padding:0.75rem 1.5rem; border-radius:12px; font-weight:700; text-decoration:none; font-size:0.88rem; display:inline-flex; align-items:center; gap:6px;">
                <span>⬅️</span> العودة لقائمة المنتجات
            </a>
        </div>
    </div>

    <?php if ($flashError !== ''): ?>
        <div style="background:#fef2f2; border:1.5px solid #fecaca; color:#991b1b; padding:1.25rem; border-radius:16px; margin-bottom:1.75rem; font-weight:700; display:flex; align-items:center; gap:0.75rem;">
            <span style="font-size:1.5rem;">⚠️</span>
            <div><?= esc($flashError) ?></div>
        </div>
    <?php endif; ?>

    <form class="admin-form" method="post" action="<?= esc(admin_url('product_save.php')) ?>" id="product-form" novalidate onsubmit="return handleFormSubmit()">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
        
        <input type="hidden" name="is_brand_product" id="is_brand_product_input" value="<?= $isFlatPrice && in_array('brands', $product['categories'], true) ? '1' : '0' ?>">
        <input type="hidden" name="brand_id" id="brand_id_input" value="<?= $brandIdFromUrl ?? (int)($product['brand_id'] ?? 0) ?>">
        <input type="hidden" name="slug" value="<?= esc($product['slug']) ?>">
        <input type="hidden" name="season" value="both">
        <input type="hidden" name="active" value="1">
        <input type="hidden" name="sort_order" value="<?= (int) $product['sort_order'] ?>">

        <!-- CARD 1: Basic Info & Categories -->
        <div class="pe-card">
            <div class="pe-card-header">
                <h3 class="pe-card-title">
                    <span>🏷️</span> التصنيفات والأقسام التابعة
                </h3>
                <span style="font-size:0.78rem; color:#64748b;">يمكنك اختيار أكثر من قسم للعطر</span>
            </div>

            <!-- Categories Chips -->
            <div style="margin-bottom:1.5rem;">
                <label class="pe-label" style="margin-bottom:0.75rem;">اختر أقسام المنتج:</label>
                <div class="pe-cat-grid">
                    <?php foreach ($cats as $c):
                        if ($c['slug'] === 'offers' || $c['slug'] === 'brands') continue;
                        $isChecked = in_array($c['slug'], $product['categories'], true);
                        $isFlat = in_array($c['slug'], $flatCatSlugs, true);
                    ?>
                    <label class="pe-cat-pill <?= $isChecked ? 'is-active' : '' ?>">
                        <input type="checkbox" name="categories[]" value="<?= esc((string)$c['slug']) ?>" <?= $isChecked ? 'checked' : '' ?> data-flat="<?= $isFlat ? '1' : '0' ?>" onchange="togglePillActive(this)">
                        <span><?= esc((string)($c['name_ar'] ?: $c['name_en'])) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bestseller & Brand options -->
            <div style="display:flex; flex-wrap:wrap; gap:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;">
                <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:800; font-size:0.9rem; color:#0f172a;">
                    <input type="checkbox" name="is_bestseller" value="1" <?= $product['is_bestseller'] ? 'checked' : '' ?> style="accent-color:#d4af37; width:20px; height:20px;">
                    <span>🔥 تمييز العطر كـ «الأكثر مبيعاً» (Bestseller)</span>
                </label>
            </div>
        </div>

        <!-- CARD 2: Names & Notes -->
        <div class="pe-card">
            <div class="pe-card-header">
                <h3 class="pe-card-title">
                    <span>📝</span> الأسماء والنفحات العطرية
                </h3>
            </div>

            <!-- Name Arabic & English -->
            <div class="pe-grid-2">
                <div class="pe-field">
                    <label class="pe-label">اسم العطر بالعربي <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name_ar" class="pe-input" required value="<?= esc($product['name_ar']) ?>" dir="rtl" placeholder="مثلاً: أومبري ليذر">
                </div>
                <div class="pe-field">
                    <label class="pe-label">اسم العطر بالإنجليزي <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name_en" class="pe-input" required value="<?= esc($product['name_en']) ?>" placeholder="e.g. Ombre Leather">
                </div>
            </div>

            <!-- Olfactory Notes (Arabic & English) -->
            <div class="pe-grid-2">
                <div class="pe-field">
                    <label class="pe-label">النفحات والمكونات العطرية (عربي)</label>
                    <input type="text" name="notes_ar" class="pe-input" value="<?= esc($product['notes_ar']) ?>" dir="rtl" placeholder="مثلاً: الهيل، الجلد، والباتشولي">
                </div>
                <div class="pe-field">
                    <label class="pe-label">النفحات العطرية (English)</label>
                    <input type="text" name="notes_en" class="pe-input" value="<?= esc($product['notes_en']) ?>" placeholder="e.g. Cardamom, Leather, Patchouli">
                </div>
            </div>

            <!-- Descriptions -->
            <div class="pe-grid-2">
                <div class="pe-field">
                    <label class="pe-label">الوصف الترويجي للعطر (عربي)</label>
                    <textarea name="description_ar" class="pe-textarea" rows="3" dir="rtl"><?= esc($product['description_ar']) ?></textarea>
                </div>
                <div class="pe-field">
                    <label class="pe-label">Description (English)</label>
                    <textarea name="description_en" class="pe-textarea" rows="3"><?= esc($product['description_en']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- CARD 3: Luxury Image Studio -->
        <div class="pe-card">
            <div class="pe-card-header">
                <h3 class="pe-card-title">
                    <span>🖼️</span> صورة العطر الرئيسية
                </h3>
            </div>

            <div style="border: 2px dashed #d4af37; border-radius: 16px; padding: 2rem; text-align: center; background: #fffdfa;">
                <div id="image-preview" style="margin-bottom:1.25rem; <?= $product['primary_image_key'] !== 'default' && $product['primary_image_key'] !== '' ? '' : 'display:none;' ?>">
                    <?php
                    $previewSrc = '';
                    if (!empty($product['primary_image_key']) && $product['primary_image_key'] !== 'default') {
                        if (str_starts_with($product['primary_image_key'], 'http')) {
                            $previewSrc = $product['primary_image_key'];
                        } elseif (str_starts_with($product['primary_image_key'], 'img_') || str_contains($product['primary_image_key'], '.')) {
                            $previewSrc = storefront_url('assets/uploads/' . ltrim($product['primary_image_key'], '/'));
                        } else {
                            $previewSrc = storefront_url('assets/img/' . $product['primary_image_key'] . '.jpg');
                        }
                    }
                    ?>
                    <img src="<?= esc($previewSrc) ?>" style="max-width:220px; max-height:220px; border-radius:14px; box-shadow:0 8px 25px rgba(0,0,0,0.12); border:2px solid #ffffff;">
                </div>
                <input type="text" id="primary_image_key" name="primary_image_key" value="<?= esc($product['primary_image_key']) ?>" style="display:none;">
                <button type="button" id="btn-upload-trigger" class="btn-gold-action" style="padding:0.85rem 2rem; font-size:0.95rem; margin:0 auto;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span>اختر أو غيّر صورة العطر من جهازك</span>
                </button>
                <input type="file" id="image-upload-input" style="display:none;" accept="image/*">
            </div>
        </div>

        <!-- CARD 4: Pricing, Sizes & UNLIMITED Stock System -->
        <div class="pe-card">
            <div class="pe-card-header">
                <div>
                    <h3 class="pe-card-title">
                        <span>💰</span> الأسعار والأحجام ونظام المخزون الذكي
                    </h3>
                    <span style="font-size:0.8rem; color:#64748b;">حدد أحجام العطر وسعر كل حجم مع إمكانية جعل المخزون غير محدود</span>
                </div>
                <button type="button" id="add-variant" class="btn-add-var">
                    <span>➕</span> إضافة حجم إضافي
                </button>
            </div>

            <!-- Flat Price (for offers/brands) -->
            <div id="flat-price-section" style="margin-bottom:1.5rem; <?= $isFlatPrice ? '' : 'display:none;' ?>">
                <div style="background:#fffbeb; border:1.5px solid #fde68a; border-radius:16px; padding:1.5rem;">
                    <div style="font-weight:800; color:#92400e; margin-bottom:1rem; font-size:0.95rem;">
                        💰 سعر موحد — منتج بدون أحجام
                    </div>
                    <div class="pe-grid-3">
                        <div class="pe-field">
                            <label class="pe-label">السعر (ج.م) <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="price" id="flat-price" class="pe-input" value="<?= esc($flatPrice) ?>" placeholder="0.00">
                        </div>
                        <div class="pe-field">
                            <label class="pe-label">السعر قبل الخصم (اختياري)</label>
                            <input type="text" name="compare_at_price" class="pe-input" value="<?= esc($flatCompare) ?>" placeholder="0.00">
                        </div>
                        <div class="pe-field">
                            <div class="pe-label">
                                <span>المخزون</span>
                                <label class="unlimited-toggle-wrap">
                                    <input type="checkbox" name="flat_unlimited_stock" id="flat_unlimited_stock" value="1" <?= $flatStock < 0 ? 'checked' : '' ?> onchange="toggleFlatUnlimited(this)">
                                    <span>♾️ غير محدود</span>
                                </label>
                            </div>
                            <div id="flat_unlimited_badge" class="unlimited-badge-active" style="<?= $flatStock < 0 ? '' : 'display:none;' ?>">
                                <span>♾️</span> متوفر دائماً (كمية غير محدودة)
                            </div>
                            <input type="number" name="stock" id="flat_stock_input" class="pe-input" value="<?= $flatStock < 0 ? -1 : $flatStock ?>" style="<?= $flatStock < 0 ? 'display:none;' : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variants List -->
            <div id="variants-section" style="<?= $isFlatPrice ? 'display:none;' : '' ?>">
                <div id="variants-container">
                    <?php foreach ($variants as $idx => $v): 
                        $isVarUnl = (int)$v['stock'] < 0;
                    ?>
                        <div class="pe-variant-card variant-row">
                            <div class="pe-field">
                                <label class="pe-label">الحجم بالعربي (مثلاً: 100 مل) <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="variants[<?= $idx ?>][label_ar]" class="pe-input" value="<?= esc($v['label_ar']) ?>" dir="rtl" placeholder="100 مل" required>
                                <input type="hidden" name="variants[<?= $idx ?>][label_en]" value="<?= esc($v['label_en']) ?>">
                            </div>
                            <div class="pe-field">
                                <label class="pe-label">السعر (ج.م) <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="variants[<?= $idx ?>][price]" class="pe-input" value="<?= esc($v['price']) ?>" placeholder="0.00" required>
                            </div>
                            <div class="pe-field">
                                <div class="pe-label">
                                    <span>المخزون</span>
                                    <label class="unlimited-toggle-wrap">
                                        <input type="checkbox" name="variants[<?= $idx ?>][is_unlimited]" value="1" <?= $isVarUnl ? 'checked' : '' ?> onchange="toggleVariantUnlimited(this)">
                                        <span>♾️ غير محدود</span>
                                    </label>
                                </div>
                                <div class="unlimited-badge-active var-unlimited-badge" style="<?= $isVarUnl ? '' : 'display:none;' ?>">
                                    <span>♾️</span> غير محدود
                                </div>
                                <input type="number" name="variants[<?= $idx ?>][stock]" value="<?= $isVarUnl ? -1 : (int)$v['stock'] ?>" class="pe-input variant-stock-input" style="<?= $isVarUnl ? 'display:none;' : '' ?>">
                            </div>
                            <input type="hidden" name="variants[<?= $idx ?>][sort_order]" value="<?= (int) $v['sort_order'] ?>">
                            <?php if ($idx > 0): ?>
                                <button type="button" class="btn-del-var remove-variant" title="حذف هذا الحجم">✕</button>
                            <?php else: ?>
                                <div style="width:44px;"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- CARD 5: WhatsApp Instant Broadcast -->
        <div style="background: linear-gradient(135deg, #064e3b 0%, #065f46 100%); border: 1.5px solid #059669; border-radius: 20px; padding: 1.5rem 2rem; color:#ffffff; margin-bottom: 2rem; box-shadow: 0 10px 25px rgba(6, 95, 70, 0.25);">
            <label style="display:flex; align-items:center; gap:1.25rem; cursor:pointer;">
                <input type="checkbox" name="broadcast_to_whatsapp" value="1" style="accent-color:#34d399; width:24px; height:24px; cursor:pointer; flex-shrink:0;">
                <div>
                    <span style="font-weight:900; font-size:1.05rem; color:#ffffff; display:flex; align-items:center; gap:8px;">
                        <span>📢</span> إرسال إشعار فوري لجميع العملاء عبر بوت الواتساب بهذا العطر
                    </span>
                    <p style="color:#a7f3d0; font-size:0.85rem; margin:0.35rem 0 0; line-height:1.4;">
                        يقوم البوت تلقائياً بإرسال رسالة ترويجية جذابة بالاسم والسعر والرابط المباشر إلى جميع عملاء المتجر.
                    </p>
                </div>
            </label>
        </div>

        <!-- Sticky Save Action Bar -->
        <div style="text-align:center; padding:1.5rem 0 3rem 0;">
            <button type="submit" id="mainSubmitBtn" class="btn-gold-action" style="width:100%; max-width:480px; padding:1.25rem; font-size:1.15rem;">
                <span>💾</span> حفظ ونشر التعديلات الآن
            </button>
            <div style="margin-top:1rem;">
                <a href="<?= esc(admin_url('products.php')) ?>" style="color:#64748b; font-weight:700; text-decoration:none; font-size:0.9rem;">إلغاء والعودة لقائمة المنتجات</a>
            </div>
        </div>

    </form>
</div>

<script>
function handleFormSubmit() {
    var submitBtn = document.getElementById('mainSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳</span> جاري الحفظ والتحديث...';
    }
    return true;
}

function togglePillActive(chk) {
    var pill = chk.closest('.pe-cat-pill');
    if (chk.checked) {
        pill.classList.add('is-active');
    } else {
        pill.classList.remove('is-active');
    }
}

window.toggleFlatUnlimited = function(chk) {
    var input = document.getElementById('flat_stock_input');
    var badge = document.getElementById('flat_unlimited_badge');
    if (chk.checked) {
        input.value = -1;
        input.style.display = 'none';
        badge.style.display = 'flex';
    } else {
        input.value = 50;
        input.style.display = 'block';
        badge.style.display = 'none';
    }
};

window.toggleVariantUnlimited = function(chk) {
    var row = chk.closest('.variant-row');
    var input = row.querySelector('.variant-stock-input');
    var badge = row.querySelector('.var-unlimited-badge');
    if (chk.checked) {
        input.value = -1;
        input.style.display = 'none';
        badge.style.display = 'flex';
    } else {
        input.value = 10;
        input.style.display = 'block';
        badge.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const addVariantBtn = document.getElementById('add-variant');
    const variantsContainer = document.getElementById('variants-container');

    // Add new variant row
    let variantCount = <?= count($variants) ?>;
    if (addVariantBtn && variantsContainer) {
        addVariantBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'pe-variant-card variant-row';
            row.innerHTML = `
                <div class="pe-field">
                    <label class="pe-label">الحجم بالعربي (مثلاً: 50 مل) <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="variants[${variantCount}][label_ar]" class="pe-input" dir="rtl" placeholder="50 مل" required>
                    <input type="hidden" name="variants[${variantCount}][label_en]" value="">
                </div>
                <div class="pe-field">
                    <label class="pe-label">السعر (ج.م) <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="variants[${variantCount}][price]" class="pe-input" placeholder="0.00" required>
                </div>
                <div class="pe-field">
                    <div class="pe-label">
                        <span>المخزون</span>
                        <label class="unlimited-toggle-wrap">
                            <input type="checkbox" name="variants[${variantCount}][is_unlimited]" value="1" checked onchange="toggleVariantUnlimited(this)">
                            <span>♾️ غير محدود</span>
                        </label>
                    </div>
                    <div class="unlimited-badge-active var-unlimited-badge" style="display:flex;">
                        <span>♾️</span> غير محدود
                    </div>
                    <input type="number" name="variants[${variantCount}][stock]" value="-1" class="pe-input variant-stock-input" style="display:none;">
                </div>
                <input type="hidden" name="variants[${variantCount}][sort_order]" value="${variantCount}">
                <button type="button" class="btn-del-var remove-variant" title="حذف هذا الحجم">✕</button>
            `;
            variantsContainer.appendChild(row);
            
            const arInput = row.querySelector('input[dir="rtl"]');
            const enHidden = row.querySelector('input[type="hidden"]');
            arInput.addEventListener('input', () => enHidden.value = arInput.value);

            variantCount++;
        });

        variantsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-variant')) {
                e.target.closest('.variant-row').remove();
            }
        });
    }

    // Image Upload Logic
    const uploadInput = document.getElementById('image-upload-input');
    const uploadBtn = document.getElementById('btn-upload-trigger');
    const previewContainer = document.getElementById('image-preview');

    if (uploadBtn && uploadInput) {
        uploadBtn.addEventListener('click', () => uploadInput.click());
        uploadInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            const formData = new FormData();
            formData.append('image', file);
            
            const originalHtml = uploadBtn.innerHTML;
            uploadBtn.innerHTML = '<span>⏳</span> جاري رفع الصورة...';
            uploadBtn.disabled = true;

            fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const filename = data.filename;
                    const url = data.url;
                    document.getElementById('primary_image_key').value = filename;
                    previewContainer.style.display = 'block';
                    previewContainer.querySelector('img').src = url;
                } else { 
                    alert(data.error || 'فشل رفع الصورة'); 
                }
            }).catch(err => {
                console.error(err);
                alert('حدث خطأ أثناء رفع الصورة');
            }).finally(() => {
                uploadBtn.innerHTML = originalHtml;
                uploadBtn.disabled = false;
            });
        });
    }
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
