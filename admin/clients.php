<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_clients');

$pdo = medal_pdo();
$clients = [];
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$totalRows = 0;
$totalPages = 1;
if ($pdo !== null) {
    try {
        $searchWhere = '';
        $searchParams = [];
        if ($search !== '') {
            $s = '%' . $search . '%';
            $searchWhere = ' AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
            $searchParams = [$s, $s, $s];
        }
        
        $sql = "
            SELECT 
                c.id AS client_id,
                c.name AS name,
                c.email AS email,
                c.phone AS phone,
                c.created_at AS created_at,
                (SELECT COUNT(*) FROM orders WHERE 
                    (customer_phone = c.phone AND c.phone IS NOT NULL AND c.phone != '') OR 
                    (customer_email = c.email AND (c.phone IS NULL OR c.phone = '') AND c.email IS NOT NULL AND c.email != '')
                ) AS order_count,
                (SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE 
                    (customer_phone = c.phone AND c.phone IS NOT NULL AND c.phone != '') OR 
                    (customer_email = c.email AND (c.phone IS NULL OR c.phone = '') AND c.email IS NOT NULL AND c.email != '')
                ) AS total_revenue,
                1 AS is_registered
            FROM clients c
            WHERE 1=1 $searchWhere

            UNION ALL

            SELECT 
                NULL AS client_id,
                MAX(o.customer_name) AS name,
                MAX(o.customer_email) AS email,
                o.customer_phone AS phone,
                MIN(o.created_at) AS created_at,
                COUNT(*) AS order_count,
                SUM(o.subtotal) AS total_revenue,
                0 AS is_registered
            FROM orders o
            WHERE 
                (o.customer_phone IS NULL OR o.customer_phone = '' OR o.customer_phone NOT IN (SELECT phone FROM clients WHERE phone IS NOT NULL AND phone != ''))
                AND 
                (o.customer_email IS NULL OR o.customer_email = '' OR o.customer_email NOT IN (SELECT email FROM clients WHERE email IS NOT NULL AND email != ''))
            GROUP BY o.customer_phone
        ";
        
        $fullSql = $sql . " ORDER BY created_at DESC";
        $allClients = $pdo->query($fullSql)->fetchAll();
        $totalRows = count($allClients);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) $page = 1;
        
        $offset = ($page - 1) * $perPage;
        $clients = array_slice($allClients, $offset, $perPage);

        // Calculate statistics
        $statTotalClients = count($allClients);
        $statRegistered = 0;
        $statGuests = 0;
        $statTotalRevenue = 0.0;
        foreach ($allClients as $c) {
            if ($c['is_registered']) {
                $statRegistered++;
            } else {
                $statGuests++;
            }
            $statTotalRevenue += (float)$c['total_revenue'];
        }
    } catch (Throwable $e) {
        $clients = [];
        $statTotalClients = 0;
        $statRegistered = 0;
        $statGuests = 0;
        $statTotalRevenue = 0.0;
    }
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions" style="margin-bottom: 1.5rem;">
    <div>
        <h1><?= esc(t('admin_clients')) ?></h1>
        <p class="admin-lead" style="margin-bottom: 0;"><?= esc(t('admin_clients_lead')) ?></p>
    </div>
    <div class="admin-actions">
        <a href="clients_export.php" class="admin-btn admin-btn--secondary">📊 تصدير إكسل</a>
        <a href="client_edit.php" class="admin-btn admin-btn--primary"><?= esc(t('admin_new_client')) ?></a>
    </div>
</div>

<!-- KPI Stats Cards -->
<div class="admin-stats" style="margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
    <div class="admin-stat">
        <div>
            <span>إجمالي قاعدة العملاء</span>
            <strong><?= $statTotalClients ?></strong>
        </div>
        <div class="admin-stat-icon-box" style="background: rgba(212,175,55,0.1); color: var(--admin-gold);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
    </div>
    
    <div class="admin-stat" style="border-inline-start: 3px solid var(--admin-success);">
        <div>
            <span>عملاء مسجلين (أعضاء)</span>
            <strong style="color: var(--admin-success);"><?= $statRegistered ?></strong>
        </div>
        <div class="admin-stat-icon-box" style="background: rgba(16,185,129,0.1); color: var(--admin-success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4.5"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
        </div>
    </div>
    
    <div class="admin-stat" style="border-inline-start: 3px solid var(--admin-info);">
        <div>
            <span>عملاء زوار (ضيوف)</span>
            <strong style="color: var(--admin-info);"><?= $statGuests ?></strong>
        </div>
        <div class="admin-stat-icon-box" style="background: rgba(59,130,246,0.1); color: var(--admin-info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
        </div>
    </div>
    
    <div class="admin-stat" style="border-inline-start: 3px solid var(--admin-gold);">
        <div>
            <span>إجمالي المشتريات للعملاء</span>
            <strong style="color: var(--admin-gold);"><?= number_format($statTotalRevenue, 2) ?> <small style="font-size: 0.85rem; font-weight: 600;"><?= esc(t('currency')) ?></small></strong>
        </div>
        <div class="admin-stat-icon-box" style="background: rgba(212,175,55,0.1); color: var(--admin-gold);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>
</div>

<!-- Search Bar -->
<div class="admin-card" style="margin-bottom: 2rem; padding: 1.25rem 1.5rem; border-radius: 12px; border-inline-start: 4px solid var(--admin-gold);">
    <form method="GET" style="display:flex; gap:1.25rem; align-items:flex-end; flex-wrap:wrap; margin:0;">
        <div style="flex: 1; min-width: 260px;">
            <label for="search" style="margin: 0 0 0.5rem; font-size: 0.85rem; font-weight: 700; display:block; color: var(--admin-text-muted);">البحث في سجل العملاء</label>
            <input type="text" id="search" name="search" value="<?= esc($search) ?>" placeholder="ابحث باسم العميل، البريد الإلكتروني، أو رقم الهاتف..." class="admin-input" style="padding:.65rem 1rem; width:100%; border-radius: 8px;">
        </div>
        <div style="display: flex; gap: .75rem; align-items: center;">
            <button type="submit" class="admin-btn admin-btn--primary" style="padding:.65rem 1.75rem; border-radius: 8px; font-weight: 700;">🔍 بحث</button>
            <?php if ($search !== ''): ?>
                <a href="<?= esc(admin_url('clients.php')) ?>" class="admin-btn admin-btn--secondary" style="padding:.65rem 1.5rem; border-radius: 8px; text-align:center;">إعادة تعيين</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.25); color: #166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600; display: flex; align-items: center; gap: 8px;">
        <span>✅</span>
        <span><?= current_lang() === 'ar' ? 'تم حفظ بيانات العميل بنجاح!' : 'Customer saved successfully!' ?></span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.25); color: #991b1b; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600; display: flex; align-items: center; gap: 8px;">
        <span>🗑️</span>
        <span><?= current_lang() === 'ar' ? 'تم حذف حساب العميل بنجاح!' : 'Customer account deleted successfully!' ?></span>
    </div>
<?php endif; ?>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($clients === []): ?>
    <p class="admin-muted"><?= esc(t('admin_no_customers')) ?></p>
<?php else: ?>
    <div class="admin-table-wrap" style="box-shadow: var(--admin-shadow); border-radius: 12px; overflow: hidden; border: 1px solid var(--admin-border);">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="font-size: 0.85rem; padding: 14px 18px;"><?= esc(t('label_name')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px;"><?= esc(t('label_email')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px;"><?= esc(t('admin_th_phone')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px; text-align: center;"><?= esc(t('admin_th_orders')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px; text-align: right;"><?= esc(t('admin_th_revenue')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px; text-align: center;"><?= esc(t('admin_th_status')) ?></th>
                    <th style="font-size: 0.85rem; padding: 14px 18px; text-align: center;"><?= esc(t('admin_th_actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr style="transition: background var(--admin-transition);">
                        <td data-label="<?= esc(t('label_name')) ?>" style="padding: 14px 18px; font-weight: 700; color: var(--admin-text);">
                            <?= esc((string)$c['name']) ?>
                        </td>
                        <td data-label="<?= esc(t('label_email')) ?>" style="padding: 14px 18px; color: var(--admin-text-muted); font-size: 0.85rem;"><?= esc((string)$c['email'] ?: '-') ?></td>
                        <td data-label="<?= esc(t('admin_th_phone')) ?>" style="padding: 14px 18px; font-weight: 500; font-family: monospace; font-size: 0.9rem;"><?= esc((string)$c['phone'] ?: '-') ?></td>
                        <td data-label="<?= esc(t('admin_th_orders')) ?>" style="padding: 14px 18px; text-align: center; font-weight: 700;"><?= (int)$c['order_count'] ?></td>
                        <td data-label="<?= esc(t('admin_th_revenue')) ?>" style="padding: 14px 18px; text-align: right; font-weight: 800; color: var(--admin-gold);"><?= number_format((float)$c['total_revenue'], 2) ?> <?= esc(t('currency')) ?></td>
                        <td data-label="<?= esc(t('admin_th_status')) ?>" style="padding: 14px 18px; text-align: center;">
                            <?php if ($c['is_registered']): ?>
                                <span class="admin-badge admin-badge--success" style="background: rgba(16,185,129,0.1); color: var(--admin-success); border: 1px solid rgba(16,185,129,0.15); padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    👤 <?= current_lang() === 'ar' ? 'عميل مسجل' : 'Registered' ?>
                                </span>
                            <?php else: ?>
                                <span class="admin-badge" style="background: rgba(107,114,128,0.1); color: var(--admin-text-muted); border: 1px solid rgba(107,114,128,0.15); padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    🛒 <?= current_lang() === 'ar' ? 'عميل زائر' : 'Guest' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= esc(t('admin_th_actions')) ?>" style="padding: 14px 18px; text-align: center;">
                            <div class="admin-actions" style="display:flex; align-items:center; justify-content: center; gap:0.5rem; flex-wrap:wrap;">
                                <a href="client_statement.php?email=<?= urlencode((string)$c['email']) ?>&phone=<?= urlencode((string)$c['phone']) ?>" class="admin-btn admin-btn--sm admin-btn--secondary" style="border-radius: 6px; padding: 5px 10px; font-weight: 700;" title="<?= current_lang() === 'ar' ? 'عرض كشف حساب العميل' : 'View Customer Statement' ?>">
                                    📄 <?= current_lang() === 'ar' ? 'كشف حساب' : 'Statement' ?>
                                </a>
                                <?php if ($c['is_registered']): ?>
                                    <a href="client_edit.php?id=<?= (int)$c['client_id'] ?>" class="admin-btn admin-btn--sm" style="border-radius: 6px; padding: 5px 10px;"><?= esc(t('admin_edit')) ?></a>
                                    <form action="client_delete.php" method="POST" style="display:inline;" onsubmit="return confirm('<?= current_lang() === 'ar' ? 'هل أنت متأكد من حذف هذا العميل نهائياً؟' : 'Are you sure you want to permanently delete this client?' ?>');">
                                        <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$c['client_id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger" style="padding: 5px 10px; border-radius:6px; cursor:pointer; font-weight: 700;">
                                            <?= current_lang() === 'ar' ? 'حذف' : 'Delete' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="client_edit.php?email=<?= urlencode((string)$c['email']) ?>&name=<?= urlencode((string)$c['name']) ?>" class="admin-btn admin-btn--sm admin-btn--primary" style="border-radius: 6px; padding: 5px 10px;">
                                        <?= esc(t('admin_register_client')) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
<div class="admin-pagination" style="display:flex; justify-content:center; align-items:center; gap:.5rem; margin-top:1.5rem; flex-wrap:wrap;">
    <?php
    $paginationParams = $_GET;
    unset($paginationParams['page']);
    $baseQuery = http_build_query($paginationParams);
    $baseUrl = admin_url('clients.php?' . ($baseQuery ? $baseQuery . '&' : ''));
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
        <?= $totalRows ?> عميل (صفحة <?= $page ?> من <?= $totalPages ?>)
    </span>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
