<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_products');
$isAr = current_lang() === 'ar';

$pdo = medal_pdo();
$rows = [];
$db_error = '';
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$totalRows = 0;
$totalPages = 1;

if ($pdo !== null) {
    try {
        if (!isset($_SESSION['_migrated_products_brand'])) {
            try { $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_brand_product TINYINT(1) DEFAULT 0"); }
            catch (Throwable $e) {}
            $_SESSION['_migrated_products_brand'] = true;
        }
        
        $searchWhere = '';
        $searchParams = [];
        if ($search !== '') {
            $searchWhere = ' AND (p.name_en LIKE ? OR p.name_ar LIKE ?)';
            $s = '%' . $search . '%';
            $searchParams = [$s, $s];
        }
        
        $baseSql = "FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug IN ('offers', 'brands'))" . $searchWhere;
        $countSql = "SELECT COUNT(*) " . $baseSql;
        $countSt = $pdo->prepare($countSql);
        $countSt->execute($searchParams);
        $totalRows = (int) $countSt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) $page = 1;
        $rows = $pdo->prepare("SELECT p.* " . $baseSql . " ORDER BY p.sort_order ASC, p.id ASC LIMIT " . $perPage . " OFFSET " . (($page - 1) * $perPage));
        $rows->execute($searchParams);
        $rows = $rows->fetchAll();
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
        $rows = [];
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= $isAr ? 'المنتجات' : 'Products' ?></h1>
        <p class="admin-lead" style="margin-bottom:0"><?= $isAr ? 'إدارة جميع المنتجات في المتجر.' : 'Manage all products in the store.' ?></p>
    </div>
    <div class="admin-actions">
        <a class="btn-admin" href="<?= esc(admin_url('product_edit.php')) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-inline-end: 0.5rem;"><path d="M12 5v14M5 12h14"/></svg>
            <?= $isAr ? 'إضافة عطر جديد' : 'New Perfume' ?>
        </a>
    </div>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($db_error !== ''): ?>
    <div class="admin-error">
        <p>حدث خطأ في قاعدة البيانات:</p>
        <code><?= esc($db_error) ?></code>
        <p style="margin-top:1rem">تأكد من استيراد ملف <code>database/schema.sql</code> في قاعدة البيانات الجديدة.</p>
    </div>
<?php else: ?>

<!-- Search Bar -->
<div class="admin-card" style="margin-bottom:1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap; margin:0;">
        <div style="flex: 1; min-width: 200px;">
            <label for="search" style="margin: 0 0 0.3rem; font-size: 0.85rem; display:block;">بحث عن عطر</label>
            <input type="text" id="search" name="search" value="<?= esc($search) ?>" placeholder="اسم العطر (عربي أو إنجليزي)..." class="admin-input" style="padding:.55rem .85rem; width:100%;">
        </div>
        <div style="display: flex; gap: .5rem;">
            <button type="submit" class="admin-btn admin-btn--primary" style="padding:.55rem 1.25rem;">بحث</button>
            <?php if ($search !== ''): ?>
                <a href="<?= esc(admin_url('products.php')) ?>" class="admin-btn admin-btn--secondary" style="padding:.55rem 1.25rem; text-align:center;">إعادة تعيين</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($rows === []): ?>
    <div class="admin-card" style="text-align:center; padding:3rem">
        <p class="admin-muted"><?= esc(t('admin_no_products')) ?></p>
        <a class="btn-admin" href="<?= esc(admin_url($newProductUrl)) ?>"><?= esc(t('admin_new_product')) ?></a>
    </div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px"><?= esc(t('admin_th_id')) ?></th>
                    <th style="width: 80px">الصورة</th>
                    <th><?= esc(t('admin_th_name_en')) ?></th>
                    <th><?= esc(t('admin_th_category')) ?></th>
                    <th><?= esc(t('admin_th_flags')) ?></th>
                    <th style="width: 80px">المخزون</th>
                    <th style="width: 80px">المشاهدات</th>
                    <th style="width: 100px"><?= esc(t('admin_th_active')) ?></th>
                    <th style="width: 100px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="<?= esc(t('admin_th_id')) ?>"><span class="admin-muted">#<?= (int) $r['id'] ?></span></td>
                        <td data-label="الصورة">
                            <?php 
                            $imgStyle = product_image_style($r['primary_image_key']);
                            // Extract URL from style if it exists, or use default
                            $bgImg = 'none';
                            if (preg_match('/url\(\'(.*?)\'\)/', $imgStyle, $matches)) {
                                $bgImg = "url('" . $matches[1] . "')";
                            }
                            ?>
                            <div class="admin-thumb" style="width:48px; height:48px; border-radius:10px; background-color:var(--admin-page-bg); background-image:<?= $bgImg ?>; background-size:cover; background-position:center; border:1px solid var(--admin-table-border); box-shadow:var(--admin-shadow-sm);"></div>
                        </td>
                        <td data-label="<?= esc(t('admin_th_name_en')) ?>">
                            <div style="font-weight:600; color:var(--admin-heading)"><?= esc((string) $r['name_en']) ?></div>
                            <div class="admin-muted" style="font-size:0.8rem"><?= esc((string) $r['slug']) ?></div>
                        </td>
                        <td data-label="<?= esc(t('admin_th_category')) ?>">
                            <span style="background:var(--admin-nav-link-hover-bg); padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem"><?= esc((string) $r['category']) ?></span>
                        </td>
                        <td data-label="<?= esc(t('admin_th_flags')) ?>">
                            <?php if (!empty($r['is_bestseller'])): ?>
                                <span class="admin-badge admin-badge--pending" style="font-size:0.7rem"><?= esc(t('admin_flag_bestseller')) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($r['is_offer'])): ?>
                                <span class="admin-badge admin-badge--processing" style="font-size:0.7rem"><?= esc(t('admin_flag_offer')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="المخزون">
                            <?php 
                            $totalStock = 0;
                            $isUnlimited = false;
                            if ($pdo !== null) {
                                try {
                                    $stk = $pdo->prepare('SELECT SUM(stock) as total, MIN(stock) as min_stk FROM product_variants WHERE product_id = ?');
                                    $stk->execute([(int)$r['id']]);
                                    $stkRow = $stk->fetch();
                                    if ($stkRow && (int)($stkRow['min_stk'] ?? 0) < 0) {
                                        $isUnlimited = true;
                                    } else {
                                        $totalStock = (int)($stkRow['total'] ?? 0);
                                    }
                                } catch (Throwable $e) {
                                    $totalStock = 'N/A';
                                }
                            }
                            ?>
                            <?php if ($isUnlimited): ?>
                                <span style="display:inline-block; padding:0.25rem 0.6rem; border-radius:8px; font-weight:800; font-size:0.78rem; background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; white-space:nowrap;">
                                    ♾️ غير محدود
                                </span>
                            <?php else: ?>
                                <span style="font-weight:700; color:<?= $totalStock === 0 ? '#dc2626' : ($totalStock <= 5 ? '#d97706' : '#059669') ?>; font-size:0.95rem;">
                                    <?= $totalStock ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="المشاهدات">
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.9rem;color:#000;font-weight:600">
                                👁️ <?= (int) ($r['view_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td data-label="<?= esc(t('admin_th_active')) ?>">
                            <?php if (!empty($r['active'])): ?>
                                <span class="admin-badge admin-badge--delivered" style="font-size:0.7rem"><?= esc(t('admin_yes')) ?></span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--cancelled" style="font-size:0.7rem"><?= esc(t('admin_no')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.5rem; justify-content:flex-end">
                                <?php
$editUrl = 'product_edit.php?id=' . (int)$r['id'];
?>
<a class="btn-admin btn-admin--sm" href="<?= esc(admin_url($editUrl)) ?>" title="<?= esc(t('admin_edit')) ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button type="button"
                                        class="btn-admin btn-admin--sm btn-admin--danger"
                                        title="حذف المنتج"
                                        onclick="confirmDeleteProduct(<?= (int)$r['id'] ?>, '<?= esc(addslashes((string)$r['name_en'])) ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
</div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; // inner if rows ?>
    
    <?php if ($totalPages > 1): ?>
    <div class="admin-pagination" style="display:flex; justify-content:center; align-items:center; gap:.5rem; margin-top:1.5rem; flex-wrap:wrap;">
        <?php
        $paginationParams = $_GET;
        unset($paginationParams['page']);
        $baseQuery = http_build_query($paginationParams);
        $baseUrl = admin_url('products.php?' . ($baseQuery ? $baseQuery . '&' : ''));
        ?>
        <?php if ($page > 1): ?>
            <a href="<?= esc($baseUrl . 'page=' . ($page - 1)) ?>" class="admin-btn admin-btn--sm" style="padding:.4rem .8rem; text-decoration:none;">السابق</a>
        <?php endif; ?>
        
        <?php
        $startPg = max(1, $page - 2);
        $endPg = min($totalPages, $page + 2);
        if ($startPg > 1): ?>
            <a href="<?= esc($baseUrl . 'page=1') ?>" class="admin-btn admin-btn--sm" style="padding:.4rem .8rem; text-decoration:none;">1</a>
            <?php if ($startPg > 2): ?><span style="padding:0 .25rem; color:var(--admin-text-muted);">...</span><?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
            <a href="<?= esc($baseUrl . 'page=' . $i) ?>" class="admin-btn admin-btn--sm" style="padding:.4rem .8rem; text-decoration:none; <?= $i === $page ? 'background:var(--admin-btn-bg); color:var(--admin-btn-text); font-weight:700;' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($endPg < $totalPages): ?>
            <?php if ($endPg < $totalPages - 1): ?><span style="padding:0 .25rem; color:var(--admin-text-muted);">...</span><?php endif; ?>
            <a href="<?= esc($baseUrl . 'page=' . $totalPages) ?>" class="admin-btn admin-btn--sm" style="padding:.4rem .8rem; text-decoration:none;"><?= $totalPages ?></a>
        <?php endif; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="<?= esc($baseUrl . 'page=' . ($page + 1)) ?>" class="admin-btn admin-btn--sm" style="padding:.4rem .8rem; text-decoration:none;">التالي</a>
        <?php endif; ?>
        
        <span style="margin-right:1rem; font-size:.85rem; color:var(--admin-text-muted);">
            <?= $totalRows ?> منتج (صفحة <?= $page ?> من <?= $totalPages ?>)
        </span>
    </div>
    <?php endif; ?>
    
<?php endif; // outer if pdo ?>

<style>
.admin-thumb:not([style*="background-image"]) {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.admin-thumb:not([style*="background-image"])::after {
    content: "No Image";
    font-size: 0.6rem;
    color: var(--admin-text-faint);
    text-align: center;
}
.btn-admin--danger {
    background: #fff0f0 !important;
    color: #e53e3e !important;
    border-color: #fec5c5 !important;
}
.btn-admin--danger:hover {
    background: #e53e3e !important;
    color: #fff !important;
    border-color: #e53e3e !important;
}
/* Delete Modal */
.delete-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.delete-modal-box {
    background: #fff;
    border-radius: 16px;
    padding: 2rem 2.5rem;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    text-align: center;
    font-family: 'Tajawal', sans-serif;
}
.delete-modal-icon {
    width: 60px;
    height: 60px;
    background: #fff0f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    color: #e53e3e;
}
.delete-modal-box h3 {
    margin: 0 0 0.5rem;
    font-size: 1.2rem;
    font-weight: 700;
    color: #111;
}
.delete-modal-box p {
    color: #666;
    margin: 0 0 1.5rem;
    font-size: 0.95rem;
    line-height: 1.6;
}
.delete-modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}
.delete-modal-actions .btn-cancel {
    padding: 0.6rem 1.4rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    color: #555;
    font-family: 'Tajawal', sans-serif;
    transition: all 0.2s;
}
.delete-modal-actions .btn-cancel:hover {
    background: #f5f5f5;
}
.delete-modal-actions .btn-confirm-delete {
    padding: 0.6rem 1.4rem;
    border-radius: 8px;
    border: none;
    background: #e53e3e;
    color: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: 'Tajawal', sans-serif;
    transition: all 0.2s;
}
.delete-modal-actions .btn-confirm-delete:hover {
    background: #c53030;
}
</style>

<!-- Delete Confirmation Modal -->
<div class="delete-modal-overlay" id="delete-product-modal" style="display:none;">
    <div class="delete-modal-box">
        <div class="delete-modal-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <h3>حذف المنتج</h3>
        <p id="delete-modal-msg">هل أنت متأكد من حذف هذا المنتج؟ لا يمكن التراجع عن هذه العملية.</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">إلغاء</button>
            <form id="delete-product-form" method="POST" action="<?= esc(admin_url('product_delete.php')) ?>" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="id" id="delete-product-id" value="">
                <input type="hidden" name="delete_product" value="1">
                <button type="submit" class="btn-confirm-delete">نعم، احذف المنتج</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteProduct(id, name) {
    document.getElementById('delete-product-id').value = id;
    document.getElementById('delete-modal-msg').textContent = 'هل أنت متأكد من حذف المنتج "' + name + '"؟ لا يمكن التراجع عن هذه العملية.';
    document.getElementById('delete-product-modal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('delete-product-modal').style.display = 'none';
}
// Close on overlay click
document.getElementById('delete-product-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
