<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_faqs');

$pdo = medal_pdo();
$rows = [];
$edit = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$error = isset($_GET['err']) ? $_GET['err'] : '';

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Exception $e) {
        $rows = [];
    }
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM faqs WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch();
    }
}

require __DIR__ . '/_layout_start.php';
?>

<h1><?= esc(t('admin_faqs')) ?></h1>
<p class="admin-lead"><?= esc(t('admin_faqs_lead')) ?></p>

<?php if ($error): ?>
    <div class="admin-alert is-error" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:4px; margin-bottom:1rem; border: 1px solid #fecaca;">
        <?= esc($error) ?>
    </div>
<?php endif; ?>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-card">
    <h2 style="margin-top:0;font-size:1.05rem"><?= $edit ? esc(t('admin_edit')) : esc(t('admin_new_faq')) ?></h2>
    <form class="admin-form" method="post" action="<?= esc(admin_url('faq_save.php')) ?>">
        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
        
        <div class="admin-form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom:1rem;">
            <div>
                <label for="question_en"><?= esc(t('admin_label_question_en')) ?></label>
                <input type="text" id="question_en" name="question_en" required value="<?= esc($edit ? (string) $edit['question_en'] : '') ?>" style="width:100%; margin-bottom:1rem; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
                
                <label for="answer_en"><?= esc(t('admin_label_answer_en')) ?></label>
                <textarea id="answer_en" name="answer_en" required style="width:100%; height:120px; padding:0.5rem; border:1px solid #ccc; border-radius:4px;"><?= esc($edit ? (string) $edit['answer_en'] : '') ?></textarea>
            </div>
            <div>
                <label for="question_ar"><?= esc(t('admin_label_question_ar')) ?></label>
                <input type="text" id="question_ar" name="question_ar" required value="<?= esc($edit ? (string) $edit['question_ar'] : '') ?>" dir="rtl" style="width:100%; margin-bottom:1rem; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
                
                <label for="answer_ar"><?= esc(t('admin_label_answer_ar')) ?></label>
                <textarea id="answer_ar" name="answer_ar" required dir="rtl" style="width:100%; height:120px; padding:0.5rem; border:1px solid #ccc; border-radius:4px;"><?= esc($edit ? (string) $edit['answer_ar'] : '') ?></textarea>
            </div>
        </div>

        <label for="sort_order"><?= esc(t('admin_label_sort_order')) ?></label>
        <input type="number" id="sort_order" name="sort_order" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>" style="width:100px; margin-bottom:1rem; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
        
        <p style="margin-top:1.5rem"><button type="submit" class="btn-admin"><?= esc(t('admin_save')) ?></button></p>
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th><?= esc(t('admin_th_sort')) ?></th>
                <th><?= esc(t('admin_th_question_en')) ?></th>
                <th style="width:200px"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td><strong><?= esc((string) $r['question_en']) ?></strong><br><small><?= esc((string) $r['question_ar']) ?></small></td>
                    <td>
                        <a class="btn-admin" href="<?= esc(admin_url('faqs.php?edit=' . (int) $r['id'])) ?>"><?= esc(t('admin_edit')) ?></a>
                        <form method="post" action="<?= esc(admin_url('faq_delete.php')) ?>" style="display:inline" onsubmit="return confirm(<?= admin_js_string('admin_confirm_delete_faq') ?>);">
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
