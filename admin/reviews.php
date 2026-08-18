<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = current_lang() === 'ar' ? 'تقييمات المنتجات' : 'Product Reviews';

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($pdo !== null) {
    try {
        $rows = $pdo->query('
            SELECT pr.*, p.name_ar AS product_name_ar, p.name_en AS product_name_en, p.slug AS product_slug 
            FROM product_reviews pr 
            JOIN products p ON pr.product_id = p.id 
            ORDER BY pr.created_at DESC
        ')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }

    if ($editId > 0) {
        $st = $pdo->prepare('SELECT pr.*, p.name_ar AS product_name_ar, p.name_en AS product_name_en FROM product_reviews pr JOIN products p ON pr.product_id = p.id WHERE pr.id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= current_lang() === 'ar' ? 'تقييمات وآراء العملاء' : 'Product Reviews & Ratings' ?></h1>
<p class="admin-lead"><?= current_lang() === 'ar' ? 'إدارة تقييمات وآراء العملاء على المنتجات المختلفة وتعديلها أو حذفها.' : 'Manage customer reviews and ratings on products, edit details, or remove inappropriate feedback.' ?></p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<?php if ($edit): ?>
<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem">
        <?= current_lang() === 'ar' ? 'تعديل التقييم الخاص بـ: ' : 'Edit Review for: ' ?>
        <strong style="color:var(--gold)"><?= esc(current_lang() === 'ar' ? $edit['product_name_ar'] : $edit['product_name_en']) ?></strong>
    </h2>
    <form class="admin-form" method="post" action="<?= esc(admin_url('review_save.php')) ?>">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        
        <label for="customer_name"><?= current_lang() === 'ar' ? 'اسم العميل' : 'Customer Name' ?></label>
        <input type="text" id="customer_name" name="customer_name" required value="<?= esc((string) $edit['customer_name']) ?>">
        
        <label for="rating"><?= current_lang() === 'ar' ? 'التقييم (عدد النجوم)' : 'Rating (Stars)' ?></label>
        <select id="rating" name="rating" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-elevated); color: var(--ink);">
            <option value="5" <?= $edit['rating'] === 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5/5)</option>
            <option value="4" <?= $edit['rating'] === 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4/5)</option>
            <option value="3" <?= $edit['rating'] === 3 ? 'selected' : '' ?>>⭐⭐⭐ (3/5)</option>
            <option value="2" <?= $edit['rating'] === 2 ? 'selected' : '' ?>>⭐⭐ (2/5)</option>
            <option value="1" <?= $edit['rating'] === 1 ? 'selected' : '' ?>>⭐ (1/5)</option>
        </select>
        
        <label for="review_text"><?= current_lang() === 'ar' ? 'نص التعليق / المراجعة' : 'Review Text' ?></label>
        <textarea id="review_text" name="review_text" rows="4" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-elevated); color: var(--ink); resize: vertical;"><?= esc((string) $edit['review_text']) ?></textarea>
        
        <div class="admin-form-actions-fixed" style="margin-top: 1.5rem;">
            <button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button>
            <a href="<?= esc(admin_url('reviews.php')) ?>" class="admin-btn admin-btn--secondary" style="text-decoration:none; display:inline-block; padding: 0.75rem 1.5rem; background: var(--bg-warm); border-radius:6px; color:var(--ink); font-weight:600; text-align:center;"><?= esc(t('admin_cancel')) ?></a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th><?= current_lang() === 'ar' ? 'المنتج' : 'Product' ?></th>
                <th><?= current_lang() === 'ar' ? 'العميل' : 'Customer' ?></th>
                <th><?= current_lang() === 'ar' ? 'التقييم' : 'Rating' ?></th>
                <th><?= current_lang() === 'ar' ? 'التعليق' : 'Comment' ?></th>
                <th><?= current_lang() === 'ar' ? 'التاريخ' : 'Date' ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--ink-muted); padding: 3rem 1rem;">
                        <?= current_lang() === 'ar' ? 'لا توجد تقييمات مسجلة حالياً.' : 'No reviews registered yet.' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="<?= current_lang() === 'ar' ? 'المنتج' : 'Product' ?>">
                            <a href="<?= esc(storefront_url('product.php?id=' . (int)$r['product_id'])) ?>" target="_blank" style="color:var(--gold); font-weight:700; text-decoration:none;">
                                <?= esc(current_lang() === 'ar' ? $r['product_name_ar'] : $r['product_name_en']) ?>
                            </a>
                        </td>
                        <td data-label="<?= current_lang() === 'ar' ? 'العميل' : 'Customer' ?>"><?= esc((string) $r['customer_name']) ?></td>
                        <td data-label="<?= current_lang() === 'ar' ? 'التقييم' : 'Rating' ?>" style="color: #c5a059; font-size: 1.1rem; white-space: nowrap;">
                            <?= str_repeat('★', (int)$r['rating']) ?><?= str_repeat('☆', 5 - (int)$r['rating']) ?>
                        </td>
                        <td data-label="<?= current_lang() === 'ar' ? 'التعليق' : 'Comment' ?>" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: normal;">
                            <?= esc((string)$r['review_text']) ?>
                        </td>
                        <td data-label="<?= current_lang() === 'ar' ? 'التاريخ' : 'Date' ?>"><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
                        <td class="admin-actions">
                            <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('reviews.php?edit=' . (int) $r['id'])) ?>"><?= esc(t('admin_edit')) ?></a>
                            <form method="post" action="<?= esc(admin_url('review_delete.php')) ?>" style="display:inline" onsubmit="return confirm('<?= current_lang() === 'ar' ? 'هل أنت متأكد من حذف هذا التقييم؟' : 'Are you sure you want to delete this review?' ?>');">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger"><?= esc(t('admin_delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
