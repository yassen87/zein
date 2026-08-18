<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_shipping');

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM shipping_cities ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM shipping_cities WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_shipping')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_shipping_lead')) ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= $edit ? esc(t('admin_edit_city')) : esc(t('admin_new_city')) ?></h2>
    <form class="admin-form" method="post" action="<?= esc(admin_url('shipping_save.php')) ?>">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        
        <label for="name_en"><?= esc(t('admin_th_city_en')) ?></label>
        <input type="text" id="name_en" name="name_en" required value="<?= esc($edit ? (string) $edit['name_en'] : '') ?>">
        
        <label for="name_ar"><?= esc(t('admin_th_city_ar')) ?></label>
        <input type="text" id="name_ar" name="name_ar" required value="<?= esc($edit ? (string) $edit['name_ar'] : '') ?>" dir="rtl">
        
        <label for="shipping_cost"><?= esc(t('admin_label_cost')) ?></label>
        <input type="number" id="shipping_cost" name="shipping_cost" required value="<?= $edit ? (float) $edit['shipping_cost'] : 0 ?>" step="0.01" min="0">
        
        <label for="sort_order"><?= esc(t('admin_label_sort_order')) ?></label>
        <input type="number" id="sort_order" name="sort_order" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>">
        
        <div class="admin-form-actions-fixed">
            <button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button>
            <?php if ($edit): ?>
                <a href="<?= esc(admin_url('shipping.php')) ?>" class="admin-btn admin-btn--secondary"><?= esc(t('admin_cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th><?= esc(t('admin_th_city_en')) ?></th>
                <th><?= esc(t('admin_th_city_ar')) ?></th>
                <th><?= esc(t('admin_label_cost')) ?></th>
                <th><?= esc(t('admin_th_sort')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="<?= esc(t('admin_th_city_en')) ?>"><?= esc((string) $r['name_en']) ?></td>
                    <td data-label="<?= esc(t('admin_th_city_ar')) ?>" dir="rtl"><?= esc((string) $r['name_ar']) ?></td>
                    <td data-label="<?= esc(t('admin_label_cost')) ?>"><?= esc(format_price($r['shipping_cost'])) ?></td>
                    <td data-label="<?= esc(t('admin_th_sort')) ?>"><?= (int) $r['sort_order'] ?></td>
                    <td class="admin-actions">
                        <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('shipping.php?edit=' . (int) $r['id'])) ?>"><?= esc(t('admin_edit')) ?></a>
                        <form method="post" action="<?= esc(admin_url('shipping_delete.php')) ?>" style="display:inline" onsubmit="return confirm(<?= admin_js_string('admin_confirm_delete_city') ?>);">
                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger"><?= esc(t('admin_delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
