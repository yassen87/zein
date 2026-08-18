<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_categories');

$pdo = medal_pdo();

// Auto-migrate: add image column if missing
if ($pdo && !isset($_SESSION['_migrated_categories_image'])) {
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS image VARCHAR(500) DEFAULT ''"); }
    catch (Throwable) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetchAll();
            if (empty($cols)) $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) DEFAULT ''");
        } catch (Throwable) {}
    }
    $_SESSION['_migrated_categories_image'] = true;
}

$rows   = [];
$edit   = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo !== null) {
    $rows = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
    if ($editId > 0) {
        $st = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $st->execute([$editId]);
        $edit = $st->fetch() ?: null;
    }
}

require __DIR__ . '/_layout_start.php';

$uploadDir = dirname(__DIR__) . '/assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc(t('admin_categories')) ?></h1>
        <p class="admin-lead" style="margin-bottom:0"><?= esc(t('admin_categories_lead')) ?></p>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php else: ?>

<div class="admin-grid-two-cols" style="display:grid; grid-template-columns:1fr 2fr; gap:1.5rem; align-items:start;">
    <!-- ══ FORM ══════════════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.5rem;">
        <h2 style="margin-top:0;font-size:1.1rem;margin-bottom:1.25rem;">
            <?= $edit ? esc(t('admin_edit_category')) : esc(t('admin_add_category')) ?>
        </h2>
        <form class="admin-form" method="post" action="<?= esc(admin_url('category_save.php')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">

            <div style="margin-bottom:1rem">
                <label for="name_en"><?= esc(t('admin_label_name_en')) ?></label>
                <input type="text" id="name_en" name="name_en" required
                       value="<?= esc($edit ? (string)$edit['name_en'] : '') ?>"
                       placeholder="English Name">
            </div>
            <div style="margin-bottom:1rem">
                <label for="name_ar"><?= esc(t('admin_label_name_ar')) ?></label>
                <input type="text" id="name_ar" name="name_ar" required dir="rtl"
                       value="<?= esc($edit ? (string)$edit['name_ar'] : '') ?>"
                       placeholder="الاسم بالعربي">
            </div>
            <div style="margin-bottom:1rem">
                <label for="sort_order"><?= esc(t('admin_label_sort_order')) ?></label>
                <input type="number" id="sort_order" name="sort_order"
                       value="<?= $edit ? (int)$edit['sort_order'] : 0 ?>">
            </div>

            <!-- Category Image -->
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; margin-bottom:.5rem; font-weight:600;">صورة القسم</label>
                <?php
                    $existingImg = $edit ? (string)($edit['image'] ?? '') : '';
                    $existingUrl = $existingImg !== '' ? base_url('assets/uploads/' . $existingImg) : '';
                ?>
                <div id="cat-img-preview"
                     style="width:100%; aspect-ratio:16/7; border-radius:10px; border:2px dashed #ddd; background:#f9f9f9; overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:.6rem; cursor:pointer;"
                     onclick="document.getElementById('cat_image_file').click()">
                    <?php if ($existingUrl !== ''): ?>
                        <img id="cat-img-el" src="<?= esc($existingUrl) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div id="cat-img-placeholder" style="text-align:center;color:#bbb;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            <p style="margin:.3rem 0 0;font-size:.75rem;">اضغط لرفع صورة القسم</p>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="cat_image_file" name="cat_image"
                       accept="image/jpeg,image/png,image/webp,image/gif" style="display:none"
                       onchange="previewCatImg(this)">
                <input type="hidden" name="cat_image_existing" value="<?= esc($existingImg) ?>">
                <p style="font-size:.75rem;color:#888;margin:.3rem 0 0;">أبعاد مقترحة: 800×350px. تظهر كـ header للقسم في الصفحة الرئيسية.</p>
            </div>

            <div style="display:flex;gap:.5rem">
                <button type="submit" class="btn-admin" style="flex:1"><?= esc(t('admin_save')) ?></button>
                <?php if ($edit): ?>
                    <a href="<?= esc(admin_url('categories.php')) ?>" class="btn-admin btn-admin--danger" style="text-align:center;text-decoration:none">إلغاء</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ══ TABLE ══════════════════════════════════════════════════════════ -->
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">صورة</th>
                    <th style="width:20%"><?= esc(t('admin_label_slug')) ?></th>
                    <th><?= esc(t('admin_th_en')) ?></th>
                    <th><?= esc(t('admin_th_ar')) ?></th>
                    <th style="width:8%"><?= esc(t('admin_th_sort')) ?></th>
                    <th style="width:12%"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $rImg = (string)($r['image'] ?? '');
                    $rUrl = $rImg !== '' ? base_url('assets/uploads/' . $rImg) : '';
                ?>
                <tr>
                    <td>
                        <?php if ($rUrl !== ''): ?>
                            <div style="width:48px;height:30px;border-radius:6px;overflow:hidden;border:1px solid #eee;">
                                <img src="<?= esc($rUrl) ?>" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        <?php else: ?>
                            <div style="width:48px;height:30px;border-radius:6px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;font-size:1rem;">🖼️</div>
                        <?php endif; ?>
                    </td>
                    <td><code style="background:var(--admin-nav-link-hover-bg);padding:.2rem .4rem;border-radius:4px;font-size:.82rem"><?= esc((string)$r['slug']) ?></code></td>
                    <td style="font-weight:500"><?= esc((string)$r['name_en']) ?></td>
                    <td dir="rtl" style="font-family:'Tajawal',sans-serif"><?= esc((string)$r['name_ar']) ?></td>
                    <td style="text-align:center"><?= (int)$r['sort_order'] ?></td>
                    <td>
                        <div style="display:flex;gap:.4rem;justify-content:flex-end">
                            <a class="btn-admin btn-admin--sm" href="<?= esc(admin_url('categories.php?edit=' . (int)$r['id'])) ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form method="post" action="<?= esc(admin_url('category_delete.php')) ?>" style="display:inline"
                                  onsubmit="return confirm(<?= admin_js_string('admin_confirm_delete_category') ?>);">
                                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--admin-text-faint)">لا توجد تصنيفات.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media(max-width:992px){ .admin-grid-two-cols{grid-template-columns:1fr !important;} }
.btn-admin--sm{padding:.4rem !important;display:flex;align-items:center;justify-content:center;}
#cat-img-preview:hover{border-color:#c5a059;}
</style>
<script>
function previewCatImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const box = document.getElementById('cat-img-preview');
        box.innerHTML = '<img id="cat-img-el" src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>