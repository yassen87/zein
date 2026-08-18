<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إضافة عرض جديد';

$pdo = medal_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$flashError = $_SESSION['product_flash_error'] ?? '';
$flashData = $_SESSION['product_flash_data'] ?? [];
if ($flashError !== '') {
    unset($_SESSION['product_flash_error'], $_SESSION['product_flash_data']);
}

$product = [
    'id' => 0,
    'slug' => '',
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
$flatStock = 50;

if ($pdo !== null && $id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row !== false) {
        $product = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
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
            $flatStock = (int) ($variants[0]['stock'] ?? 50);
        }
        $pageTitle = 'تعديل عرض';
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="margin-bottom:2rem; text-align:center;">
    <h1 style="font-size:1.8rem; color:#111;">
        <?= $id > 0 ? 'تعديل عرض' : 'إضافة عرض جديد' ?>
    </h1>
    <p style="color:#888;">
        <?= $id > 0 ? 'تعديل بيانات العرض' : 'أضف عرضاً خاصاً بدون أحجام — سعر واحد فقط' ?>
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
        <input type="hidden" name="categories[]" value="offers">
        <input type="hidden" name="is_brand_product" value="0">
        <input type="hidden" name="brand_id" value="">
        <input type="hidden" name="slug" value="<?= esc($product['slug']) ?>">
        <input type="hidden" name="season" value="both">
        <input type="hidden" name="active" value="1">
        <input type="hidden" name="sort_order" value="0">

        <div class="admin-card" style="padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">

            <div style="margin-bottom:2rem; display:flex; gap:2rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-weight:600;">
                    <input type="checkbox" name="is_bestseller" value="1" <?= $product['is_bestseller'] ? 'checked' : '' ?> style="accent-color:#c5a059; width:18px; height:18px;">
                    تمييز كأكثر مبيعاً (Bestseller)
                </label>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">اسم العرض (إنجليزي)</label>
                    <input type="text" name="name_en" required value="<?= esc($product['name_en']) ?>" placeholder="e.g. Sauvage Elixir" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:10px;">
                </div>
                <div>
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">اسم العرض (عربي)</label>
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

            <div style="margin-bottom:2rem;">
                <label style="font-weight:600; margin-bottom:1rem; display:block;">صورة العرض</label>
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

            <div style="margin-bottom:2rem;">
                <div style="background:#fefce8; border:1px solid #fde68a; border-radius:12px; padding:1.5rem;">
                    <label style="font-weight:600; margin-bottom:1rem; display:block; color:#92400e;">
                        💰 سعر العرض — بدون أحجام
                    </label>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.5rem;">
                        <div>
                            <label style="font-size:0.85rem; color:#666; margin-bottom:0.4rem; display:block;">السعر (ج.م)</label>
                            <input type="text" name="price" required value="<?= esc($flatPrice) ?>" placeholder="0.00" style="width:100%; padding:0.8rem; border:1px solid #fde68a; border-radius:10px; background:#fff;">
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

            <div style="text-align:center; margin-top:3rem;">
                <button type="submit" style="background:#111; color:white; padding:1.2rem 5rem; font-size:1.1rem; border-radius:50px; cursor:pointer; border:none; width:100%; font-weight:600;">حفظ ونشر العرض</button>
                <a href="<?= esc(admin_url('offers.php')) ?>" style="display:block; margin-top:1rem; color:#888; text-decoration:none;">إلغاء والعودة</a>
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