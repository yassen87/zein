<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$brandId = isset($_GET['brand_id']) ? (int) $_GET['brand_id'] : 0;
$pdo = medal_pdo();
$rows = [];
$brandName = '';
$allBrands = [];

if ($pdo !== null) {
    try {
        $allBrands = $pdo->query('SELECT id, name_en, name_ar FROM brands ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (Throwable) {}

    if ($brandId > 0) {
        $st = $pdo->prepare('
            SELECT p.*, b.name_en AS brand_name_en, b.name_ar AS brand_name_ar,
                (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) AS min_price,
                (SELECT MIN(stock) FROM product_variants WHERE product_id = p.id) AS min_stock,
                (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) AS total_stock
            FROM products p 
            JOIN product_categories pc ON p.id = pc.product_id 
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE pc.category_slug = \'brands\' AND p.brand_id = ? AND p.active = 1 
            ORDER BY p.sort_order ASC, p.id ASC
        ');
        $st->execute([$brandId]);
        $rows = $st->fetchAll();
        
        $bst = $pdo->prepare('SELECT name_ar, name_en FROM brands WHERE id = ?');
        $bst->execute([$brandId]);
        $bRow = $bst->fetch();
        $brandName = (string) ($bRow['name_ar'] ?: ($bRow['name_en'] ?? 'Brand'));
    } else {
        $rows = $pdo->query('
            SELECT p.*, b.name_en AS brand_name_en, b.name_ar AS brand_name_ar,
                (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) AS min_price,
                (SELECT MIN(stock) FROM product_variants WHERE product_id = p.id) AS min_stock,
                (SELECT SUM(stock) FROM product_variants WHERE product_id = p.id) AS total_stock
            FROM products p 
            JOIN product_categories pc ON p.id = pc.product_id 
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE pc.category_slug = \'brands\' AND p.active = 1 
            ORDER BY p.sort_order ASC, p.id ASC
        ')->fetchAll();
        $brandName = 'جميع الماركات العالمية';
    }
}

$pageTitle = 'عطور الماركات - ' . $brandName;
$activeMenu = 'brands';

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Luxury Brand Products Studio */
.bp-wrap {
    max-width: 1300px;
    margin: 0 auto 3rem auto;
    padding: 1rem;
    box-sizing: border-box;
    font-family: inherit;
}
.bp-hero {
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
.bp-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.bp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.bp-table th {
    background: #0f172a;
    color: #ffffff;
    padding: 1rem 1.25rem;
    text-align: right;
    font-weight: 700;
}
.bp-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.bp-table tr:hover {
    background: #f8fafc;
}
.bp-thumb {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    background-size: cover;
    background-position: center;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.btn-gold {
    background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
    color: #ffffff;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    transition: all 0.2s;
}
.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
    color: #ffffff;
}
.brand-filter-pill {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
}
.brand-filter-pill:hover, .brand-filter-pill.is-active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}
</style>

<div class="bp-wrap">

    <!-- Top Hero -->
    <div class="bp-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                <span style="background:rgba(212,175,55,0.2); border:1px solid #d4af37; color:#f59e0b; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.75rem; font-weight:800;">
                    👑 DESIGNER COLLECTION
                </span>
                <span style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.75rem; font-weight:700;">
                    <?= count($rows) ?> عطر مسجل
                </span>
            </div>
            <h1 style="font-size:1.65rem; font-weight:900; margin:0 0 0.35rem 0;">
                🛍️ <?= esc($brandName) ?>
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.4;">
                قائمة العطور التابعة لبيوت الماركات العالمية مع إمكانية التعديل السريع وإضافة عطور جديدة.
            </p>
        </div>

        <div style="display:flex; gap:0.75rem;">
            <a href="<?= esc(admin_url('brand_edit.php' . ($brandId > 0 ? '?brand_id=' . $brandId : ''))) ?>" class="btn-gold">
                <span>➕</span> إضافة عطر ماركة جديد
            </a>
            <a href="<?= esc(admin_url('brands.php')) ?>" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#ffffff; padding:0.85rem 1.25rem; border-radius:12px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <span>🏷️</span> إدارة الماركات
            </a>
        </div>
    </div>

    <!-- Filter by Brand Pills -->
    <?php if (!empty($allBrands)): ?>
    <div style="display:flex; flex-wrap:wrap; gap:0.6rem; margin-bottom:1.5rem;">
        <a href="<?= esc(admin_url('brand_products.php')) ?>" class="brand-filter-pill <?= $brandId === 0 ? 'is-active' : '' ?>">
            الكل (<?= count($rows) ?>)
        </a>
        <?php foreach ($allBrands as $b): ?>
            <a href="<?= esc(admin_url('brand_products.php?brand_id=' . (int)$b['id'])) ?>" class="brand-filter-pill <?= $brandId === (int)$b['id'] ? 'is-active' : '' ?>">
                <?= esc((string)($b['name_ar'] ?: $b['name_en'])) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Table of Products -->
    <div class="bp-table-wrap">
        <table class="bp-table">
            <thead>
                <tr>
                    <th style="width:70px;">الصورة</th>
                    <th>اسم العطر</th>
                    <th>الماركة</th>
                    <th style="text-align:center;">السعر</th>
                    <th style="text-align:center;">المخزون</th>
                    <th style="text-align:center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:3rem; color:#64748b;">لا توجد عطور لهذه الماركة حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): 
                        $imgKey = (string)$r['primary_image_key'];
                        $imgUrl = '';
                        if (!empty($imgKey) && $imgKey !== 'default') {
                            if (str_starts_with($imgKey, 'http')) {
                                $imgUrl = $imgKey;
                            } elseif (str_starts_with($imgKey, 'img_') || str_contains($imgKey, '.')) {
                                $imgUrl = storefront_url('assets/uploads/' . ltrim($imgKey, '/'));
                            } else {
                                $imgUrl = storefront_url('assets/img/' . $imgKey . '.jpg');
                            }
                        }
                        $isUnl = (int)($r['min_stock'] ?? 0) < 0;
                    ?>
                        <tr>
                            <td>
                                <div class="bp-thumb" style="background-image:url('<?= esc($imgUrl) ?>');"></div>
                            </td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:0.95rem;">
                                    <?= esc((string) $r['name_ar']) ?>
                                    <?php if (!empty($r['is_bestseller'])): ?>
                                        <span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:0.7rem; font-weight:800; padding:0.15rem 0.45rem; border-radius:4px; margin-inline-start:4px;">🔥 الأكثر مبيعاً</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.8rem; color:#64748b; font-weight:600;"><?= esc((string) $r['name_en']) ?></div>
                            </td>
                            <td>
                                <span style="background:#f1f5f9; color:#0f172a; padding:0.3rem 0.75rem; border-radius:6px; font-weight:700; font-size:0.8rem;">
                                    🏷️ <?= esc((string)($r['brand_name_ar'] ?: ($r['brand_name_en'] ?: 'ماركة عالمية'))) ?>
                                </span>
                            </td>
                            <td style="text-align:center; font-weight:800; color:#0f172a; font-size:1rem;">
                                <?= number_format((float)$r['min_price'], 2) ?> ج.م
                            </td>
                            <td style="text-align:center;">
                                <?php if ($isUnl): ?>
                                    <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; border-radius:6px; padding:0.25rem 0.6rem; font-size:0.75rem; font-weight:800; display:inline-block;">
                                        ♾️ غير محدود
                                    </span>
                                <?php else: ?>
                                    <span style="font-weight:700; color:<?= (int)$r['total_stock'] === 0 ? '#dc2626' : '#059669' ?>;">
                                        <?= (int)$r['total_stock'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.5rem; justify-content:center;">
                                    <a href="<?= esc(admin_url('brand_edit.php?id=' . (int) $r['id'])) ?>" style="background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; padding:0.45rem 0.85rem; border-radius:8px; font-weight:700; font-size:0.82rem; text-decoration:none;" title="تعديل">
                                        ✏️ تعديل
                                    </a>
                                    <form method="post" action="<?= esc(admin_url('product_delete.php')) ?>" style="display:inline;" onsubmit="return confirm('حذف عطر <?= esc(addslashes((string)$r['name_ar'])) ?>؟');">
                                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="delete_product" value="1">
                                        <input type="hidden" name="redirect_to" value="brand_products">
                                        <button type="submit" style="background:#fee2e2; border:1px solid #fecaca; color:#dc2626; padding:0.45rem 0.75rem; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer;" title="حذف">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
