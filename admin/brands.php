<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إدارة الماركات العالمية (International Brands)';
$activeMenu = 'brands';

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    $rows = $pdo->query('
        SELECT b.*, 
            (SELECT COUNT(*) FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_slug = \'brands\' AND p.brand_id = b.id AND p.active = 1) AS products_count
        FROM brands b 
        ORDER BY b.is_popular DESC, b.sort_order ASC, b.id ASC
    ')->fetchAll();

    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM brands WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

$totalBrands = count($rows);
$popularBrandsCount = count(array_filter($rows, fn($b) => !empty($b['is_popular'])));

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Luxury Brands Studio - Pure Vanilla CSS */
.brands-wrap {
    max-width: 1300px;
    margin: 0 auto 3rem auto;
    padding: 1rem;
    box-sizing: border-box;
    font-family: inherit;
}
.br-hero {
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
.br-hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.br-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.br-stat-title {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.br-stat-val {
    font-size: 1.5rem;
    font-weight: 900;
    color: #0f172a;
}
.br-grid-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.75rem;
    align-items: start;
}
.br-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}
.br-card-title {
    font-size: 1.1rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 1.25rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.br-field {
    margin-bottom: 1.2rem;
}
.br-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.4rem;
}
.br-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.8rem 1rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 0.9rem;
    color: #0f172a;
    transition: all 0.2s;
    font-family: inherit;
}
.br-input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
}
.br-btn-gold {
    background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
    color: #ffffff;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    transition: all 0.2s;
    width: 100%;
}
.br-btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}
.br-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
}
.br-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.br-table th {
    background: #0f172a;
    color: #ffffff;
    padding: 0.85rem 1rem;
    text-align: right;
    font-weight: 700;
}
.br-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.br-table tr:hover {
    background: #f8fafc;
}
.br-logo-box {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.br-logo-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.badge-popular {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

@media (max-width: 900px) {
    .br-grid-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="brands-wrap">

    <!-- Top Hero Banner -->
    <div class="br-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                <span style="background:rgba(212,175,55,0.2); border:1px solid #d4af37; color:#f59e0b; padding:0.25rem 0.75rem; border-radius:50px; font-size:0.75rem; font-weight:800;">
                    👑 BRANDS STUDIO
                </span>
            </div>
            <h1 style="font-size:1.65rem; font-weight:900; margin:0 0 0.35rem 0;">
                🏷️ إدارة الماركات العالمية والبيوت العطرية
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.4;">
                إضافة الماركات ودور العطور العالمية، وتحديد الماركات المميزة وربط العطور بها.
            </p>
        </div>

        <div>
            <a href="<?= esc(admin_url('brand_products.php')) ?>" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#ffffff; padding:0.75rem 1.5rem; border-radius:12px; font-weight:700; text-decoration:none; font-size:0.88rem; display:inline-flex; align-items:center; gap:6px;">
                <span>🛍️</span> عرض عطور الماركات المسجلة
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="br-hero-stats">
        <div class="br-stat-card">
            <div class="br-stat-title">إجمالي الماركات المسجلة</div>
            <div class="br-stat-val" style="color:#0f172a;"><?= number_format($totalBrands) ?></div>
        </div>
        <div class="br-stat-card">
            <div class="br-stat-title">الماركات الأكثر شعبية</div>
            <div class="br-stat-val" style="color:#d97706;">⭐ <?= number_format($popularBrandsCount) ?></div>
        </div>
        <div class="br-stat-card">
            <div class="br-stat-title">عطور الماركات في المتجر</div>
            <div class="br-stat-val" style="color:#059669;">
                <?= number_format(array_sum(array_map(fn($b) => (int)$b['products_count'], $rows))) ?> <span style="font-size:0.8rem; font-weight:normal;">عطر</span>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Add/Edit Form & Brands Table -->
    <div class="br-grid-layout">
        
        <!-- Left: Form -->
        <div class="br-card">
            <h2 class="br-card-title">
                <span><?= $edit ? '✏️' : '➕' ?></span>
                <?= $edit ? 'تعديل بيانات الماركة' : 'إضافة ماركة عالمية جديدة' ?>
            </h2>

            <form class="admin-form" method="post" action="<?= esc(admin_url('brand_save.php')) ?>">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
                
                <div class="br-field">
                    <label class="br-label" for="name_en">الاسم بالإنجليزية (EN) <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="name_en" name="name_en" class="br-input" required value="<?= esc($edit ? (string) $edit['name_en'] : '') ?>" placeholder="e.g. Tom Ford, Dior, Chanel">
                </div>

                <div class="br-field">
                    <label class="br-label" for="name_ar">الاسم بالعربية (AR) <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="name_ar" name="name_ar" class="br-input" required value="<?= esc($edit ? (string) $edit['name_ar'] : '') ?>" dir="rtl" placeholder="مثلاً: توم فورد، ديور، شانيل">
                </div>

                <input type="hidden" name="country_en" value="">
                <input type="hidden" name="country_ar" value="">
                <input type="hidden" name="description_ar" value="">

                <div class="br-field">
                    <label class="br-label" for="logo">شعار / صورة الماركة</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="logo" name="logo" class="br-input" value="<?= esc($edit ? (string) $edit['logo'] : '') ?>" placeholder="assets/img/brands/tomford.png">
                    </div>
                </div>

                <div class="br-field">
                    <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:800; font-size:0.88rem; color:#0f172a;">
                        <input type="checkbox" id="is_popular" name="is_popular" value="1" <?= $edit && $edit['is_popular'] ? 'checked' : '' ?> style="accent-color:#d4af37; width:18px; height:18px;">
                        <span>⭐ تمييز كـ «ماركة مشهورة / الأكثر شعبية»</span>
                    </label>
                </div>

                <div class="br-field">
                    <label class="br-label" for="sort_order">ترتيب الظهور</label>
                    <input type="number" id="sort_order" name="sort_order" class="br-input" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>">
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                    <button type="submit" class="br-btn-gold">
                        <span>💾</span> <?= $edit ? 'حفظ التعديلات' : 'إضافة الماركة الآن' ?>
                    </button>
                    <?php if ($edit): ?>
                        <a href="<?= esc(admin_url('brands.php')) ?>" style="background:#f1f5f9; color:#475569; padding:0.85rem 1.25rem; border-radius:12px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center;">
                            إلغاء
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Right: Brands Table -->
        <div class="br-table-wrap">
            <table class="br-table">
                <thead>
                    <tr>
                        <th style="width:60px;">الشعار</th>
                        <th>اسم الماركة</th>
                        <th style="text-align:center;">عدد العطور</th>
                        <th style="text-align:center;">الترتيب</th>
                        <th style="text-align:center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:#64748b;">لا توجد ماركات مسجلة حالياً.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <div class="br-logo-box">
                                        <?php if (!empty($r['logo'])): ?>
                                            <img src="<?= esc(str_starts_with($r['logo'], 'http') ? $r['logo'] : storefront_url($r['logo'])) ?>" alt="<?= esc($r['name_en']) ?>">
                                        <?php else: ?>
                                            <span style="font-size:1.2rem;">🏷️</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:800; color:#0f172a; font-size:0.92rem; display:flex; align-items:center; gap:6px;">
                                        <?= esc((string) $r['name_ar']) ?>
                                        <?php if (!empty($r['is_popular'])): ?>
                                            <span class="badge-popular">⭐ شائعة</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b; font-weight:600;"><?= esc((string) $r['name_en']) ?></div>
                                </td>
                                <td style="text-align:center;">
                                    <a href="<?= esc(admin_url('brand_products.php?brand_id=' . (int)$r['id'])) ?>" style="background:#f1f5f9; color:#0f172a; border-radius:50px; padding:0.25rem 0.75rem; font-size:0.78rem; font-weight:800; text-decoration:none;">
                                        <?= (int)$r['products_count'] ?> عطر ↗
                                    </a>
                                </td>
                                <td style="text-align:center; color:#64748b; font-weight:700;">
                                    <?= (int) $r['sort_order'] ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem; justify-content:center;">
                                        <a href="<?= esc(admin_url('brands.php?edit=' . (int) $r['id'])) ?>" style="background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; padding:0.4rem 0.75rem; border-radius:8px; font-weight:700; font-size:0.8rem; text-decoration:none;" title="تعديل">
                                            ✏️ تعديل
                                        </a>
                                        <form method="post" action="<?= esc(admin_url('brand_delete.php')) ?>" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف ماركة <?= esc(addslashes((string)$r['name_ar'])) ?>؟');">
                                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" style="background:#fee2e2; border:1px solid #fecaca; color:#dc2626; padding:0.4rem 0.75rem; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer;" title="حذف">
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

</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
