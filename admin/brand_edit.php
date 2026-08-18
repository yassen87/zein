<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إضافة وتعديل منتج ماركة عالمية';

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$brandIdFromUrl = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;

$flashError = $_SESSION['product_flash_error'] ?? '';
$flashData = $_SESSION['product_flash_data'] ?? [];
if ($flashError !== '') {
    unset($_SESSION['product_flash_error'], $_SESSION['product_flash_data']);
}

$brands = [];
if ($pdo !== null) {
    try {
        $brands = $pdo->query('SELECT id, name_en, name_ar FROM brands ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {}
}

$product = [
    'id' => 0,
    'slug' => '',
    'brand_id' => null,
    'is_bestseller' => false,
    'name_en' => '',
    'name_ar' => '',
    'description_en' => 'Premium fragrance.',
    'description_ar' => 'عطر فاخر ومميز.',
    'primary_image_key' => 'default',
];

if (!empty($flashData) && $id === 0) {
    $product['name_en'] = trim((string)($flashData['name_en'] ?? $product['name_en']));
    $product['name_ar'] = trim((string)($flashData['name_ar'] ?? $product['name_ar']));
    $product['description_en'] = trim((string)($flashData['description_en'] ?? $product['description_en']));
    $product['description_ar'] = trim((string)($flashData['description_ar'] ?? $product['description_ar']));
    $product['primary_image_key'] = trim((string)($flashData['primary_image_key'] ?? $product['primary_image_key']));
}

$flatPrice = '';
$flatCompare = '';
$flatStock = -1;

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row !== false) {
        $product = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'brand_id' => isset($row['brand_id']) ? (int)$row['brand_id'] : null,
            'is_bestseller' => !empty($row['is_bestseller']),
            'name_en' => (string) $row['name_en'],
            'name_ar' => (string) $row['name_ar'],
            'description_en' => (string) $row['description_en'],
            'description_ar' => (string) $row['description_ar'],
            'primary_image_key' => (string) $row['primary_image_key'],
        ];

        $vst = $pdo->prepare('SELECT label_en, label_ar, price, compare_at_price, stock, sort_order FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
        $vst->execute([$id]);
        $variants = $vst->fetchAll();
        if (!empty($variants)) {
            $flatPrice = (string) $variants[0]['price'];
            $flatCompare = (string) ($variants[0]['compare_at_price'] ?? '');
            $flatStock = (int) ($variants[0]['stock'] ?? -1);
        }
        $pageTitle = 'تعديل منتج ماركة';
    }
}

$selectedBrandId = $brandIdFromUrl ?? (int)($product['brand_id'] ?? 0);

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Luxury Brand Product Editor */
.be-wrap {
    max-width: 1100px;
    margin: 0 auto 3rem auto;
    padding: 1rem;
    box-sizing: border-box;
    font-family: inherit;
}
.be-hero {
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
.be-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.be-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1.5px solid #f1f5f9;
}
.be-card-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.be-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.be-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.be-field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.be-label {
    font-size: 0.85rem;
    font-weight: 800;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.be-input, .be-textarea, .be-select {
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
.be-input:focus, .be-textarea:focus, .be-select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
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

@media (max-width: 768px) {
    .be-grid-2, .be-grid-3 {
        grid-template-columns: 1fr;
    }
    .be-hero {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="be-wrap">

    <!-- Top Hero Header -->
    <div class="be-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                <span style="background:rgba(212,175,55,0.2); border:1px solid #d4af37; color:#f59e0b; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.78rem; font-weight:800;">
                    ID #<?= (int)$product['id'] ?>
                </span>
                <span style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.78rem; font-weight:700;">
                    🏷️ عطور الماركات العالمية
                </span>
            </div>
            <h1 style="font-size:1.6rem; font-weight:900; margin:0 0 0.35rem 0;">
                <span>👑</span> <?= $id > 0 ? 'تعديل عطر الماركة: ' . esc($product['name_ar'] ?: $product['name_en']) : 'إضافة عطر ماركة عالمية جديد' ?>
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.4;">
                إضافة أو تعديل عطور الدور العالمية مع السعر والمخزون غير المحدود وصورة المنتج.
            </p>
        </div>

        <div>
            <a href="<?= esc(admin_url('brand_products.php')) ?>" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#ffffff; padding:0.75rem 1.5rem; border-radius:12px; font-weight:700; text-decoration:none; font-size:0.88rem; display:inline-flex; align-items:center; gap:6px;">
                <span>⬅️</span> العودة لقائمة عطور الماركات
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
        <input type="hidden" name="categories[]" value="brands">
        <input type="hidden" name="is_brand_product" value="1">
        <input type="hidden" name="brand_id" id="brand_id_input" value="<?= $selectedBrandId ?>">
        <input type="hidden" name="slug" value="<?= esc($product['slug']) ?>">
        <input type="hidden" name="season" value="both">
        <input type="hidden" name="active" value="1">
        <input type="hidden" name="sort_order" value="0">

        <!-- CARD 1: Brand Selection -->
        <div class="be-card">
            <div class="be-card-header">
                <h3 class="be-card-title">
                    <span>🏷️</span> اختيار الماركة العالمية
                </h3>
            </div>

            <div class="be-field">
                <label class="be-label">اختر الماركة التابع لها هذا العطر <span style="color:#ef4444;">*</span></label>
                <select name="brand_id_selector" id="brand_id_selector" class="be-select" required onchange="document.getElementById('brand_id_input').value = this.value;">
                    <option value="">-- اختر الماركة العالمية --</option>
                    <?php foreach ($brands as $b):
                        $sel = ((int)$b['id'] === $selectedBrandId) ? ' selected' : '';
                    ?>
                    <option value="<?= (int)$b['id'] ?>"<?= $sel ?>><?= esc((string)($b['name_ar'] ?: $b['name_en'])) ?> (<?= esc((string)$b['name_en']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-top:1.25rem;">
                <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:800; font-size:0.9rem; color:#0f172a;">
                    <input type="checkbox" name="is_bestseller" value="1" <?= $product['is_bestseller'] ? 'checked' : '' ?> style="accent-color:#d4af37; width:20px; height:20px;">
                    <span>🔥 تمييز العطر كـ «الأكثر مبيعاً» (Bestseller)</span>
                </label>
            </div>
        </div>

        <!-- CARD 2: Names & Description -->
        <div class="be-card">
            <div class="be-card-header">
                <h3 class="be-card-title">
                    <span>📝</span> اسم العطر وتفاصيله
                </h3>
            </div>

            <div class="be-grid-2">
                <div class="be-field">
                    <label class="be-label">اسم العطر بالعربي <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name_ar" class="be-input" required value="<?= esc($product['name_ar']) ?>" dir="rtl" placeholder="مثلاً: سوفاج ديور">
                </div>
                <div class="be-field">
                    <label class="be-label">اسم العطر بالإنجليزي <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name_en" class="be-input" required value="<?= esc($product['name_en']) ?>" placeholder="e.g. Dior Sauvage">
                </div>
            </div>

            <div class="be-grid-2">
                <div class="be-field">
                    <label class="be-label">الوصف بالعربي</label>
                    <textarea name="description_ar" class="be-textarea" rows="3" dir="rtl"><?= esc($product['description_ar']) ?></textarea>
                </div>
                <div class="be-field">
                    <label class="be-label">Description (English)</label>
                    <textarea name="description_en" class="be-textarea" rows="3"><?= esc($product['description_en']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- CARD 3: Image Studio -->
        <div class="be-card">
            <div class="be-card-header">
                <h3 class="be-card-title">
                    <span>🖼️</span> صورة العطر
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

        <!-- CARD 4: Price & Stock -->
        <div class="be-card">
            <div class="be-card-header">
                <h3 class="be-card-title">
                    <span>💰</span> السعر والمخزون
                </h3>
            </div>

            <div class="be-grid-3">
                <div class="be-field">
                    <label class="be-label">السعر (ج.م) <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="price" id="flat-price" class="be-input" required value="<?= esc($flatPrice) ?>" placeholder="0.00">
                </div>
                <div class="be-field">
                    <label class="be-label">السعر قبل الخصم (اختياري)</label>
                    <input type="text" name="compare_at_price" class="be-input" value="<?= esc($flatCompare) ?>" placeholder="0.00">
                </div>
                <div class="be-field">
                    <div class="be-label">
                        <span>المخزون</span>
                        <label class="unlimited-toggle-wrap">
                            <input type="checkbox" name="flat_unlimited_stock" id="flat_unlimited_stock" value="1" <?= $flatStock < 0 ? 'checked' : '' ?> onchange="toggleFlatUnlimited(this)">
                            <span>♾️ غير محدود</span>
                        </label>
                    </div>
                    <div id="flat_unlimited_badge" class="unlimited-badge-active" style="<?= $flatStock < 0 ? '' : 'display:none;' ?>">
                        <span>♾️</span> متوفر دائماً (كمية غير محدودة)
                    </div>
                    <input type="number" name="stock" id="flat_stock_input" class="be-input" value="<?= $flatStock < 0 ? -1 : $flatStock ?>" style="<?= $flatStock < 0 ? 'display:none;' : '' ?>">
                </div>
            </div>
        </div>

        <!-- Sticky Save Action Bar -->
        <div style="text-align:center; padding:1.5rem 0 3rem 0;">
            <button type="submit" id="mainSubmitBtn" class="btn-gold-action" style="width:100%; max-width:480px; padding:1.25rem; font-size:1.15rem;">
                <span>💾</span> حفظ ونشر عطر الماركة الآن
            </button>
            <div style="margin-top:1rem;">
                <a href="<?= esc(admin_url('brand_products.php')) ?>" style="color:#64748b; font-weight:700; text-decoration:none; font-size:0.9rem;">إلغاء والعودة لقائمة عطور الماركات</a>
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

document.addEventListener('DOMContentLoaded', function() {
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