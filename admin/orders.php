<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_orders');
$isAr = current_lang() === 'ar';

$pdo = medal_pdo();

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    if ($action === 'pay_part' && $orderId > 0) {
        $amount = (float)($_POST['amount_to_pay'] ?? 0.0);
        if ($amount > 0.0) {
            $u = $pdo->prepare('UPDATE orders SET paid_amount = paid_amount + ? WHERE id = ?');
            $u->execute([$amount, $orderId]);
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if ($action === 'waive_remaining' && $orderId > 0) {
        $st = $pdo->prepare('SELECT total, paid_amount, waived_amount FROM orders WHERE id = ?');
        $st->execute([$orderId]);
        $ord = $st->fetch();
        if ($ord) {
            $remaining = (float)$ord['total'] - (float)$ord['paid_amount'] - (float)$ord['waived_amount'];
            if ($remaining > 0.0) {
                $u = $pdo->prepare('UPDATE orders SET waived_amount = waived_amount + ? WHERE id = ?');
                $u->execute([$remaining, $orderId]);
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if ($action === 'delete_order' && $orderId > 0) {
        try {
            $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
            $pdo->prepare('DELETE FROM order_internal_products WHERE order_id = ?')->execute([$orderId]);
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$orderId]);
        } catch (Throwable) {}
        header('Location: ' . admin_url('orders.php'));
        exit;
    }
}

$rows = [];
$filter = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$totalRows = 0;
$totalPages = 1;

// Statistics
$statTotalOrders = 0;
$statPendingOrders = 0;
$statActiveOrders = 0;
$statTotalSales = 0.0;

// Status counts
$statusCounts = [
    'all' => 0,
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

if ($pdo !== null) {
    try {
        // Fetch stats
        $statTotalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $statPendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $statActiveOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('processing', 'shipped')")->fetchColumn();
        $statTotalSales = (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
        
        // Fetch counts per status
        $statusCounts['all'] = $statTotalOrders;
        $counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
        foreach ($counts as $c) {
            if (isset($statusCounts[$c['status']])) {
                $statusCounts[$c['status']] = (int)$c['cnt'];
            }
        }
    } catch (Throwable $e) {
        // Fallback in case of database issue
    }

    // Build query for orders table
    $sql = 'SELECT id, order_number, status, delivered_at, customer_name, customer_email, customer_phone, subtotal, total, paid_amount, waived_amount, created_at FROM orders WHERE 1=1';
    $countSql = 'SELECT COUNT(*) FROM orders WHERE 1=1';
    $params = [];
    
    if ($filter !== '' && in_array($filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
        $sql .= ' AND status = ?';
        $countSql .= ' AND status = ?';
        $params[] = $filter;
    }
    
    if ($startDate !== '') {
        $sql .= ' AND created_at >= ?';
        $countSql .= ' AND created_at >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate !== '') {
        $sql .= ' AND created_at <= ?';
        $countSql .= ' AND created_at <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    
    if ($search !== '') {
        $sql .= ' AND (order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)';
        $countSql .= ' AND (order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)';
        $s = '%' . $search . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }
    
    // Get total count for pagination
    $countSt = $pdo->prepare($countSql);
    $countSt->execute($params);
    $totalRows = (int) $countSt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    
    $sql .= ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <h1><?= esc(t('admin_orders')) ?></h1>
    <div class="admin-actions">
        <a href="orders_export.php?<?= http_build_query($_GET) ?>" class="admin-btn admin-btn--secondary" title="تصدير قائمة الشحن للطلبات الحالية بناء على الفلترة النشطة">
            🚚 تصدير للشحن (إكسل)
        </a>
    </div>
</div>

<p class="admin-lead"><?= esc(t('admin_orders_lead')) ?></p>

<?php if (isset($_GET['msg'])): ?>
  <div class="admin-notice" style="padding:.75rem 1rem; border-radius:8px; margin-bottom:1.5rem;
       background:<?= $_GET['msg']==='email_sent' ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)' ?>;
       color:<?= $_GET['msg']==='email_sent' ? '#059669' : '#dc2626' ?>; border:1px solid <?= $_GET['msg']==='email_sent' ? 'rgba(16,185,129,.3)' : 'rgba(239,68,68,.3)' ?>;">
    <?= $_GET['msg']==='email_sent' ? '✅ تم إرسال إيميل التأكيد بنجاح.' : '❌ فشل إرسال الإيميل.' ?>
  </div>
<?php endif; ?>

<!-- Stats Cards Grid -->
<div class="admin-stats" style="margin-bottom: 2rem;">
    <div class="admin-stat">
        <strong><?= $statTotalOrders ?></strong>
        <span>إجمالي الطلبات</span>
    </div>
    <div class="admin-stat" style="border-inline-start: 4px solid var(--admin-nav-link-hover);">
        <strong><?= $statPendingOrders ?></strong>
        <span>طلبات قيد الانتظار</span>
    </div>
    <div class="admin-stat" style="border-inline-start: 4px solid #3b82f6;">
        <strong><?= $statActiveOrders ?></strong>
        <span>طلبات نشطة (تحت التجهيز/شحن)</span>
    </div>
    <div class="admin-stat" style="border-inline-start: 4px solid #166534;">
        <strong><?= number_format($statTotalSales, 2) ?> <span style="font-size:1.1rem; font-weight:700;">ج.م</span></strong>
        <span>إجمالي الإيرادات (غير الملغاة)</span>
    </div>
</div>

<!-- Filters Card -->
<div class="admin-card" style="margin-bottom:1.5rem; padding: 1.5rem;">
    <form method="GET" class="admin-form" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap; margin:0;">
        <div style="flex: 1; min-width: 150px;">
            <label for="search" style="margin: 0 0 0.5rem; font-size: 0.85rem;">بحث</label>
            <input type="text" id="search" name="search" value="<?= esc($search) ?>" placeholder="رقم الطلب، اسم العميل، هاتف، بريد..." class="admin-input" style="padding:.55rem .85rem; width:100%;">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label for="start_date" style="margin: 0 0 0.5rem; font-size: 0.85rem;">من تاريخ</label>
            <input type="date" id="start_date" name="start_date" value="<?= esc($startDate) ?>" class="admin-input" style="padding:.55rem .85rem;">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label for="end_date" style="margin: 0 0 0.5rem; font-size: 0.85rem;">إلى تاريخ</label>
            <input type="date" id="end_date" name="end_date" value="<?= esc($endDate) ?>" class="admin-input" style="padding:.55rem .85rem;">
        </div>
        <input type="hidden" name="status" value="<?= esc($filter) ?>">
        <div style="display: flex; gap: .5rem; min-width: 200px;">
            <button type="submit" class="admin-btn admin-btn--primary" style="padding:.55rem 1.25rem; flex: 1;">بحث</button>
            <a href="<?= esc(admin_url('orders.php')) ?>" class="admin-btn admin-btn--secondary" style="padding:.55rem 1.25rem; flex: 1; text-align:center;">إعادة تعيين</a>
        </div>
    </form>
</div>

<!-- Quick Status Filter Tabs -->
<div class="status-tabs" style="display:flex; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap; background:rgba(212,175,55,0.05); padding:.5rem; border-radius:12px; border:1px solid var(--admin-card-border);">
    <?php
    $tabStatuses = [
        '' => ['label' => 'الكل', 'count' => $statusCounts['all'], 'class' => ''],
        'pending' => ['label' => 'قيد الانتظار', 'count' => $statusCounts['pending'], 'class' => 'admin-badge--pending'],
        'processing' => ['label' => 'قيد التجهيز', 'count' => $statusCounts['processing'], 'class' => 'admin-badge--processing'],
        'shipped' => ['label' => 'تم الشحن', 'count' => $statusCounts['shipped'], 'class' => 'admin-badge--shipped'],
        'delivered' => ['label' => 'تم التوصيل', 'count' => $statusCounts['delivered'], 'class' => 'admin-badge--delivered'],
        'cancelled' => ['label' => 'ملغي', 'count' => $statusCounts['cancelled'], 'class' => 'admin-badge--cancelled']
    ];
    foreach ($tabStatuses as $statusVal => $info):
        $isActive = ($filter === $statusVal);
        $urlParams = $_GET;
        $urlParams['status'] = $statusVal;
        $url = admin_url('orders.php?') . http_build_query($urlParams);
        $badgeClass = $info['class'];
        
        // Active/Inactive tab styles
        $tabStyle = "text-decoration:none; padding:.5rem 1rem; border-radius:8px; font-weight:700; font-size:.9rem; display:flex; align-items:center; gap:.5rem; transition:all .2s;";
        if ($isActive) {
            $tabStyle .= "background:var(--admin-btn-bg); color:var(--admin-btn-text); box-shadow:var(--admin-shadow-sm);";
        } else {
            $tabStyle .= "color:var(--admin-text-muted); background:var(--admin-card-bg); border:1px solid var(--admin-card-border);";
        }
    ?>
        <a href="<?= esc($url) ?>" style="<?= $tabStyle ?>">
            <span><?= esc($info['label']) ?></span>
            <span class="admin-badge <?= $badgeClass ?>" style="padding:.1rem .4rem; font-size:.75rem; border-radius:6px; min-width:20px; text-align:center;">
                <?= $info['count'] ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_short')) ?></div>
<?php elseif ($rows === []): ?>
    <p class="admin-muted"><?= esc(t('admin_no_orders')) ?></p>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table" style="font-size:0.88rem;">
            <thead>
                <tr>
                    <th style="min-width:180px;">رقم الطلب / العميل</th>
                    <th style="min-width:100px;">الحالة</th>
                    <th style="min-width:170px;">المبلغ</th>
                    <th style="min-width:130px;">التاريخ</th>
                    <th style="min-width:150px; text-align:center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $total = (float) $r['total'];
                    $paid = (float)($r['paid_amount'] ?? 0.0);
                    $waived = (float)($r['waived_amount'] ?? 0.0);
                    $remaining = max(0.0, $total - $paid - $waived);
                    $isDelivered = $r['status'] === 'delivered';
                    $isCancelled = $r['status'] === 'cancelled';
                ?>
                    <tr class="<?= $isDelivered ? 'row-delivered' : ($isCancelled ? 'row-cancelled' : '') ?>">

                        <!-- Order + Customer merged -->
                        <td>
                            <a href="<?= esc(admin_url('order_view.php?id=' . (int)$r['id'])) ?>" style="font-weight:700; font-size:0.9rem; color:var(--admin-gold); letter-spacing:.02em;">
                                <?= esc((string)$r['order_number']) ?>
                            </a>
                            <div style="font-weight:600; color:var(--admin-heading); margin-top:0.15rem; font-size:0.83rem;">
                                <?= esc((string)$r['customer_name']) ?>
                            </div>
                            <?php if (!empty($r['customer_phone'])): ?>
                                <div style="font-size:0.77rem; color:var(--admin-text-muted); margin-top:0.1rem;">📞 <?= esc((string)$r['customer_phone']) ?></div>
                            <?php endif; ?>
                            <div style="font-size:0.77rem; color:var(--admin-text-muted); margin-top:0.05rem; direction:ltr;">✉️ <?= esc((string)$r['customer_email']) ?></div>
                        </td>

                        <!-- Status -->
                        <td>
                            <select class="inline-status" data-order-id="<?= (int)$r['id'] ?>" style="padding:.35rem .5rem; border-radius:8px; border:1px solid var(--admin-input-border); font-size:.82rem; font-weight:700; background:var(--admin-card-bg); cursor:pointer; width:100%; max-width:130px;">
                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>"<?= $r['status'] === $s ? ' selected' : '' ?>><?= esc(admin_order_status_label($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- Total / Payment -->
                        <td>
                            <div style="font-weight:800; font-size:0.95rem; color:var(--admin-gold);"><?= number_format($total, 2) ?> <span style="font-size:0.75rem; font-weight:500;"><?= esc(t('currency')) ?></span></div>
                            <div style="font-size:0.76rem; margin-top:0.25rem; line-height:1.6;">
                                <span style="color:#10b981;">✔ مدفوع: <?= number_format($paid, 2) ?></span><br>
                                <?php if ($waived > 0): ?>
                                    <span style="color:#8b5cf6;">⬇ ممسوح: <?= number_format($waived, 2) ?></span><br>
                                <?php endif; ?>
                                <span style="color:<?= $remaining > 0 ? '#ef4444' : '#10b981' ?>; font-weight:700;"><?= $remaining > 0 ? '⚠ متبقي: ' . number_format($remaining, 2) : '✅ مسدّد بالكامل' ?></span>
                            </div>
                            <?php if ($remaining > 0): ?>
                                <div style="margin-top:0.4rem; display:flex; gap:0.25rem; flex-wrap:wrap;">
                                    <button type="button" onclick="quickPayOrder(<?= (int)$r['id'] ?>, <?= $remaining ?>)" style="background:#10b981; color:#fff; border:none; border-radius:5px; padding:0.2rem 0.45rem; font-size:0.7rem; font-weight:700; cursor:pointer;">💵 دفع جزء</button>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Created At -->
                        <td style="font-size:0.82rem; white-space:nowrap; color:var(--admin-text-muted);">
                            <?= date('Y-m-d', strtotime($r['created_at'])) ?><br>
                            <span style="font-size:0.75rem;"><?= date('H:i', strtotime($r['created_at'])) ?></span>
                        </td>

                        <!-- Actions -->
                        <td style="text-align:center;">
                            <div style="display:flex; gap:0.3rem; justify-content:center; flex-wrap:wrap;">
                                <a href="<?= esc(admin_url('order_view.php?id=' . (int)$r['id'])) ?>" class="admin-btn admin-btn--sm" title="عرض">👁️</a>
                                <a href="<?= esc(admin_url('order_management.php?id=' . (int)$r['id'])) ?>" class="admin-btn admin-btn--sm" title="تعديل">✏️</a>
                                <form method="POST" action="<?= esc(admin_url('send_order_email.php')) ?>" style="display:inline; margin:0;">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="order_id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn--sm" style="background:linear-gradient(135deg,#f0dc82,#d4af37); color:#1a1508;" title="إرسال إيميل">📧</button>
                                </form>
                                <form method="POST" action="" style="display:inline; margin:0;" onsubmit="return confirm('تحذير! سيتم حذف الطلب وجميع بنوده نهائياً. هل أنت متأكد؟');">
                                    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                                    <input type="hidden" name="order_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="delete_order">
                                    <button type="submit" class="admin-btn admin-btn--sm" style="background:#ef4444; color:#fff; border-color:#c53030;" title="حذف الطلب نهائياً">🗑️</button>
                                </form>
                            </div>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>
    
    <?php if ($totalPages > 1): ?>
    <div class="admin-pagination" style="display:flex; justify-content:center; align-items:center; gap:.5rem; margin-top:1.5rem; flex-wrap:wrap;">
        <?php
        $paginationParams = $_GET;
        unset($paginationParams['page']);
        $baseQuery = http_build_query($paginationParams);
        $baseUrl = admin_url('orders.php?' . ($baseQuery ? $baseQuery . '&' : ''));
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
            <?= $totalRows ?> طلب (صفحة <?= $page ?> من <?= $totalPages ?>)
        </span>
    </div>
    <?php endif; ?>
    
<?php endif; ?>

<script>
(function() {
    var csrf = '<?= esc(admin_csrf_token()) ?>';
    var url = '<?= esc(admin_url('ajax_update_order_field.php')) ?>';

    function saveField(orderId, field, value, cb) {
        var form = new FormData();
        form.append('order_id', orderId);
        form.append('field', field);
        form.append('value', value);
        form.append('csrf', csrf);
        fetch(url, { method: 'POST', body: form })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (cb) cb(res.success);
            })
            .catch(function() { if (cb) cb(false); });
    }

    // Status dropdown change → save + auto-reload
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('inline-status')) {
            var sel = e.target;
            var orderId = sel.getAttribute('data-order-id');
            sel.disabled = true;
            sel.style.opacity = '0.5';
            saveField(orderId, 'status', sel.value, function(ok) {
                location.reload();
            });
        }
    });

    // Paid amount input removed
})();

function quickPayOrder(orderId, maxAmount) {
    var amount = prompt("أدخل المبلغ الذي تم دفعه (الحد الأقصى: " + maxAmount + "):", maxAmount);
    if (amount === null || amount === "") return;
    amount = parseFloat(amount);
    if (isNaN(amount) || amount <= 0 || amount > maxAmount) {
        alert("يرجى إدخال مبلغ صحيح ضمن الحد الأقصى المسموح به.");
        return;
    }
    
    var form = document.createElement("form");
    form.method = "POST";
    form.action = "";
    
    var csrfInput = document.createElement("input");
    csrfInput.type = "hidden";
    csrfInput.name = "csrf";
    csrfInput.value = '<?= esc(admin_csrf_token()) ?>';
    form.appendChild(csrfInput);
    
    var idInput = document.createElement("input");
    idInput.type = "hidden";
    idInput.name = "order_id";
    idInput.value = orderId;
    form.appendChild(idInput);
    
    var actionInput = document.createElement("input");
    actionInput.type = "hidden";
    actionInput.name = "action";
    actionInput.value = "pay_part";
    form.appendChild(actionInput);
    
    var amountInput = document.createElement("input");
    amountInput.type = "hidden";
    amountInput.name = "amount_to_pay";
    amountInput.value = amount;
    form.appendChild(amountInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
<style>
.row-delivered { opacity:0.72; }
.row-delivered td { background:rgba(16,185,129,.05) !important; }
.row-cancelled { opacity:0.55; }
.row-cancelled td { background:rgba(239,68,68,.04) !important; }
.admin-table td { vertical-align: top; padding: 0.75rem 0.85rem; }
.admin-table thead th { padding: 0.65rem 0.85rem; font-size: 0.82rem; letter-spacing: .03em; }
</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
