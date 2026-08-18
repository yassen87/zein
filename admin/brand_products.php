<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$brandId = isset($_GET['brand_id']) ? (int) $_GET['brand_id'] : 0;
$pdo = medal_pdo();
$rows = [];
$brandName = '';

if ($pdo !== null) {
    if (!isset($_SESSION['_migrated_products_brand'])) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_brand_product TINYINT(1) DEFAULT 0");
        } catch (Throwable) {}
        $_SESSION['_migrated_products_brand'] = true;
    }

    if ($brandId > 0) {
        $st = $pdo->prepare('SELECT p.* FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_slug = \'brands\' AND p.active = 1 ORDER BY p.sort_order ASC, p.id ASC');
        $st->execute();
        $rows = $st->fetchAll();
        
        $bst = $pdo->prepare('SELECT name_en FROM brands WHERE id = ?');
        $bst->execute([$brandId]);
        $brandName = (string) ($bst->fetch()['name_en'] ?? 'Brand');
    } else {
        $rows = $pdo->query('SELECT p.* FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_slug = \'brands\' AND p.active = 1 ORDER BY p.sort_order ASC, p.id ASC')->fetchAll();
        $brandName = 'جميع الماركات';
    }
}

$pageTitle = 'عطور الماركات - ' . $brandName;
require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc($brandName) ?></h1>
        <p class="admin-lead" style="margin-bottom:0">إدارة عطور الماركات المسجلة.</p>
    </div>
    <div class="admin-actions">
        <a class="btn-admin" href="<?= esc(admin_url('brand_edit.php' . ($brandId > 0 ? '?brand_id=' . $brandId : ''))) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.5rem;"><path d="M12 5v14M5 12h14"/></svg>
            إضافة عطر ماركة
        </a>
    </div>
</div>

<?php if ($rows === []): ?>
    <div class="admin-card" style="text-align:center; padding:3rem">
        <p class="admin-muted">لا توجد عطور لهذه الماركة حالياً.</p>
    </div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px">الصورة</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th style="width: 100px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?php 
                            $imgStyle = product_image_style($r['primary_image_key']);
                            $bgImg = 'none';
                            if (preg_match('/url\(\'(.*?)\'\)/', $imgStyle, $matches)) {
                                $bgImg = "url('" . $matches[1] . "')";
                            }
                            ?>
                            <div class="admin-thumb" style="width:48px; height:48px; border-radius:10px; background-image:<?= $bgImg ?>; background-size:cover; background-position:center; border:1px solid #eee;"></div>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= esc((string) $r['name_en']) ?></div>
                            <div style="font-size:0.8rem; color:#888;"><?= esc((string) $r['name_ar']) ?></div>
                        </td>
                        <td><?= esc((string) $r['category']) ?></td>
                        <td>
                            <div style="display:flex; gap:0.5rem; justify-content:flex-end">
                                <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('brand_edit.php?id=' . (int) $r['id'])) ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="post" action="<?= esc(admin_url('product_delete.php')) ?>" style="display:inline;"
                                      onsubmit="return confirm('حذف المنتج: <?= esc(addslashes((string)$r['name_ar'])) ?>؟')">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="delete_product" value="1">
                                    <input type="hidden" name="redirect_to" value="brand_products">
                                    <button type="submit" class="btn-admin btn-admin--sm" style="background:#fff0f0; color:#e53e3e; border:1px solid #fec5c5;" title="حذف">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    </button>
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
