<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إدارة العروض';
$pdo       = medal_pdo();
$success   = '';
$error     = '';

if ($pdo) {
    if (isset($_GET['saved']))   $success = '✅ تم حفظ العرض بنجاح!';
    if (isset($_GET['deleted'])) $success = '🗑️ تم حذف العرض بنجاح!';
}

$offers = [];
if ($pdo) {
    try {
        $offers = $pdo->query(
            "SELECT p.*,
             (SELECT pv.price FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.sort_order ASC, pv.id ASC LIMIT 1) AS price,
             (SELECT pv.compare_at_price FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.sort_order ASC, pv.id ASC LIMIT 1) AS compare_at_price,
             (SELECT pv.stock FROM product_variants pv WHERE pv.product_id = p.id ORDER BY pv.sort_order ASC, pv.id ASC LIMIT 1) AS stock
             FROM products p
             WHERE p.active = 1 AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug = 'offers')
             ORDER BY p.sort_order ASC, p.id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $error = 'خطأ في جلب العروض: ' . $e->getMessage();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1>🏷️ إدارة العروض</h1>
        <p class="admin-lead" style="margin-bottom:0;">المنتجات في قسم العروض — بدون أحجام، بسعر ثابت.</p>
    </div>
    <a href="<?= esc(admin_url('offer_edit.php')) ?>"
       class="btn-admin" style="background:#c5a059; color:#fff;">✚ إضافة عرض جديد</a>
</div>

<?php if ($error !== ''): ?>
<div class="admin-error" style="margin-bottom:1.5rem;"><?= esc($error) ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
<div style="background:#d4edda;color:#155724;padding:1rem 1.5rem;border-radius:10px;border:1px solid #c3e6cb;margin-bottom:1.5rem;font-weight:600;">
    <?= esc($success) ?>
</div>
<?php endif; ?>

<?php if (empty($offers)): ?>
<div class="admin-card" style="text-align:center; padding:4rem 2rem; border:1px dashed var(--admin-card-border);">
    <span style="font-size:3rem; display:block; margin-bottom:1rem;">🏷️</span>
    <p class="admin-muted" style="margin:0 0 1.25rem; font-size:1.05rem;">لا توجد عروض حالياً.</p>
    <a href="<?= esc(admin_url('offer_edit.php')) ?>"
       class="btn-admin" style="background:#c5a059; color:#fff;">✚ أضف أول عرض</a>
</div>
<?php else: ?>

<?php
$totalOffers  = count($offers);
$activeOffers = count(array_filter($offers, fn($o) => !empty($o['active'])));
?>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <div class="admin-card" style="padding:1.25rem; text-align:center;">
        <div style="font-size:1.8rem; font-weight:800; color:#c5a059;"><?= $totalOffers ?></div>
        <div class="admin-muted" style="font-size:.82rem;">إجمالي العروض</div>
    </div>
    <div class="admin-card" style="padding:1.25rem; text-align:center;">
        <div style="font-size:1.8rem; font-weight:800; color:#28a745;"><?= $activeOffers ?></div>
        <div class="admin-muted" style="font-size:.82rem;">عروض نشطة</div>
    </div>
    <div class="admin-card" style="padding:1.25rem; text-align:center;">
        <div style="font-size:1.8rem; font-weight:800; color:#6c757d;"><?= $totalOffers - $activeOffers ?></div>
        <div class="admin-muted" style="font-size:.82rem;">عروض موقوفة</div>
    </div>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th style="width:65px;">الصورة</th>
                <th>اسم العرض</th>
                <th style="width:120px;">السعر</th>
                <th style="width:90px;">المخزون</th>
                <th style="width:80px;">الحالة</th>
                <th style="width:70px; text-align:center;">الترتيب</th>
                <th style="width:160px; text-align:center;">العمليات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($offers as $o): ?>
        <tr>
            <td><span class="admin-muted">#<?= (int)$o['id'] ?></span></td>
            <td>
                <?php if (!empty($o['primary_image_key']) && $o['primary_image_key'] !== 'default'): ?>
                    <img src="<?= esc(base_url('assets/uploads/' . $o['primary_image_key'])) ?>"
                         style="width:50px; height:50px; object-fit:cover; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.1);">
                <?php else: ?>
                    <span style="font-size:1.8rem; display:block; text-align:center;">🏷️</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:600; color:var(--admin-heading);"><?= esc($o['name_ar']) ?></div>
                <div class="admin-muted" style="font-size:.76rem;"><?= esc($o['name_en']) ?></div>
            </td>
            <td>
                <div style="font-weight:700; color:#c5a059;"><?= format_price((float)($o['price'] ?? 0)) ?></div>
                <?php if (!empty($o['compare_at_price']) && (float)$o['compare_at_price'] > (float)($o['price'] ?? 0)): ?>
                    <div class="admin-muted" style="font-size:.74rem; text-decoration:line-through;"><?= format_price((float)$o['compare_at_price']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php $stock = (int)($o['stock'] ?? 0); ?>
                <span style="font-weight:600; color:<?= $stock > 0 ? '#28a745' : '#dc3545' ?>;">
                    <?= $stock ?>
                </span>
            </td>
            <td>
                <span class="admin-badge <?= !empty($o['active']) ? 'admin-badge--processing' : 'admin-badge--cancelled' ?>">
                    <?= !empty($o['active']) ? 'نشط' : 'موقف' ?>
                </span>
            </td>
            <td style="text-align:center;"><span class="admin-muted"><?= (int)$o['sort_order'] ?></span></td>
            <td style="text-align:center;">
                <div style="display:flex; gap:.4rem; justify-content:center;">
                    <a href="<?= esc(admin_url('offer_edit.php?id=' . (int)$o['id'])) ?>"
                       class="btn-admin btn-admin--sm" style="padding:.32rem .8rem; font-size:.8rem;">✏️ تعديل</a>
                    <form method="post" action="<?= esc(admin_url('product_delete.php')) ?>"
                          style="display:inline;"
                          onsubmit="return confirm('حذف العرض: <?= esc(addslashes($o['name_ar'])) ?>؟')">
                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <input type="hidden" name="delete_product" value="1">
                        <input type="hidden" name="redirect_to" value="offers">
                        <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm"
                                style="padding:.32rem .8rem; font-size:.8rem;">🗑️</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>