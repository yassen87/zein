<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_brands');

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    $rows = $pdo->query('SELECT * FROM brands ORDER BY is_popular DESC, sort_order ASC, id ASC')->fetchAll();
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM brands WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc(t('admin_brands')) ?></h1>
        <p class="admin-lead" style="margin-bottom:0"><?= esc(t('admin_brands_lead')) ?></p>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-grid-two-cols" style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    <div class="admin-card">
        <h2 style="margin-top:0;font-size:1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.5rem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= $edit ? esc(t('admin_edit_brand')) : esc(t('admin_add_brand')) ?>
        </h2>
        <form class="admin-form" method="post" action="<?= esc(admin_url('brand_save.php')) ?>">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem">
                <div>
                    <label for="name_en">الاسم (EN)</label>
                    <input type="text" id="name_en" name="name_en" required value="<?= esc($edit ? (string) $edit['name_en'] : '') ?>" placeholder="Chanel">
                </div>
                <div>
                    <label for="name_ar">الاسم (AR)</label>
                    <input type="text" id="name_ar" name="name_ar" required value="<?= esc($edit ? (string) $edit['name_ar'] : '') ?>" dir="rtl" placeholder="شانيل">
                </div>
            </div>

            <input type="hidden" name="country_en" value="">
            <input type="hidden" name="country_ar" value="">
            <input type="hidden" name="description_ar" value="">

            <div style="margin-bottom:1rem">
                <label for="logo">رابط الشعار</label>
                <div style="display:flex; gap:0.5rem">
                    <input type="text" id="logo" name="logo" value="<?= esc($edit ? (string) $edit['logo'] : '') ?>" placeholder="assets/img/brands/logo.png" style="flex:1">
                    <button type="button" class="btn-admin btn-upload" data-target="logo" style="padding:0.6rem">رفع صورة</button>
                </div>
            </div>

            <div style="margin-bottom:1rem">
                <label for="is_popular" style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                    <input type="checkbox" id="is_popular" name="is_popular" value="1" <?= $edit && $edit['is_popular'] ? 'checked' : '' ?>>
                    الماركات الأكثر شعبية
                </label>
            </div>

            <div style="margin-bottom:1.5rem">
                <label for="sort_order"><?= esc(t('admin_label_sort_order')) ?></label>
                <input type="number" id="sort_order" name="sort_order" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>">
            </div>

            <div style="display:flex;gap:0.5rem">
                <button type="submit" class="btn-admin" style="flex:1"><?= esc(t('admin_save')) ?></button>
                <?php if ($edit): ?>
                    <a href="<?= esc(admin_url('brands.php')) ?>" class="btn-admin btn-admin--danger" style="text-align:center;text-decoration:none">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 10%">الشعار</th>
                    <th>الاسم (EN)</th>
                    <th>الاسم (AR)</th>
                    <th style="width: 10%">الترتيب</th>
                    <th style="width: 15%"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?php if ($r['logo']): ?>
                                <img src="<?= esc(base_url($r['logo'])) ?>" alt="Logo" style="height:30px;width:auto;object-fit:contain">
                            <?php else: ?>
                                <span style="color:var(--admin-text-faint)">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:500"><?= esc((string) $r['name_en']) ?></td>
                        <td dir="rtl"><?= esc((string) $r['name_ar']) ?></td>
                        <td style="text-align:center"><?= (int) $r['sort_order'] ?></td>
                        <td>
                            <div style="display:flex;gap:0.4rem;justify-content:flex-end">
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('brand_products.php?brand_id=' . (int) $r['id'])) ?>" title="عرض منتجات الماركة" style="background:#3498db; color:white;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('brand_product_edit.php?brand_id=' . (int) $r['id'])) ?>" title="إضافة منتج لهذا البراند" style="background:#27ae60">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                </a>
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('brands.php?edit=' . (int) $r['id'])) ?>" title="تعديل">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="post" action="<?= esc(admin_url('brand_delete.php')) ?>" style="display:inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الماركة؟');">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm" title="حذف">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--admin-text-faint)">لا توجد ماركات حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media (max-width: 992px) {
    .admin-grid-two-cols {
        grid-template-columns: 1fr !important;
    }
}
.btn-admin--sm {
    padding: 0.4rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
