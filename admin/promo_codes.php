<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_promo_codes');

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$error = isset($_GET['err']) ? $_GET['err'] : '';

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM promo_codes ORDER BY id DESC')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM promo_codes WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_promo_codes')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_promo_codes_lead')) ?></p>

<?php if ($error === 'code_in_use'): ?>
    <div class="admin-error"><?= esc(t('admin_err_code_in_use')) ?></div>
<?php endif; ?>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= $edit ? esc(t('admin_edit_promo_code')) : esc(t('admin_new_promo_code')) ?></h2>
    <form class="admin-form" method="post" action="<?= esc(admin_url('promo_code_save.php')) ?>">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        
        <label for="code"><?= esc(t('admin_label_code')) ?></label>
        <input type="text" id="code" name="code" required value="<?= esc($edit ? (string) $edit['code'] : '') ?>" style="text-transform: uppercase;">
        
        <label for="discount_percentage"><?= esc(t('admin_label_discount')) ?></label>
        <input type="number" id="discount_percentage" name="discount_percentage" required value="<?= $edit ? (int) $edit['discount_percentage'] : 0 ?>" min="1" max="100">
        
        <label for="usage_limit"><?= esc(t('admin_label_usage_limit')) ?></label>
        <input type="number" id="usage_limit" name="usage_limit" required value="<?= $edit ? (int) $edit['usage_limit'] : 0 ?>" min="0">
        
        <div class="admin-form-row">
            <label>
                <input type="checkbox" name="active" value="1" <?= (!$edit || !empty($edit['active'])) ? 'checked' : '' ?>>
                <?= esc(t('admin_active')) ?>
            </label>
        </div>
        
        <p style="margin-top:1rem"><button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button></p>
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th><?= esc(t('admin_th_code')) ?></th>
                <th><?= esc(t('admin_th_discount')) ?></th>
                <th><?= esc(t('admin_th_used')) ?> / <?= esc(t('admin_th_limit')) ?></th>
                <th><?= esc(t('admin_th_active')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><strong><?= esc((string) $r['code']) ?></strong></td>
                    <td><?= (int) $r['discount_percentage'] ?>%</td>
                    <td><?= (int) $r['used_count'] ?> / <?= $r['usage_limit'] == 0 ? '∞' : (int) $r['usage_limit'] ?></td>
                    <td><?= !empty($r['active']) ? esc(t('admin_yes')) : esc(t('admin_no')) ?></td>
                    <td>
                        <a class="btn-admin" href="<?= esc(admin_url('promo_codes.php?edit=' . (int) $r['id'])) ?>"><?= esc(t('admin_edit')) ?></a>
                        <form method="post" action="<?= esc(admin_url('promo_code_delete.php')) ?>" style="display:inline" onsubmit="return confirm(<?= admin_js_string('admin_confirm_delete_promo_code') ?>);">
                            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" class="btn-admin btn-admin--danger"><?= esc(t('admin_delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
