<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_dashboard');

$pdo = medal_pdo();

$stats = [
    'today_orders' => 0,
    'yesterday_orders' => 0,
    'monthly_revenue' => 0.0,
    'last_month_revenue' => 0.0,
    'pending_orders' => 0,
    'total_products' => 0,
];
$salesChartLabels = [];
$salesChartData = [];
$statusChartData = [];
$recent = [];
$topProducts = [];

if ($pdo !== null) {
    try {
        $stats['today_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $stats['yesterday_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY")->fetchColumn();
        $stats['monthly_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') AND status != 'cancelled'")->fetchColumn();
        $stats['last_month_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m') AND status != 'cancelled'")->fetchColumn();
        $stats['pending_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
        $stats['total_products'] = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        $salesSt = $pdo->query("SELECT DATE(created_at) as day, SUM(total) as revenue FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled' GROUP BY DATE(created_at) ORDER BY day");
        $salesChartLabels = [];
        $salesChartData = [];
        $salesDateMap = [];
        while ($row = $salesSt->fetch()) {
            $salesDateMap[(string) $row['day']] = (float) $row['revenue'];
        }
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $salesChartLabels[] = $d;
            $salesChartData[] = $salesDateMap[$d] ?? 0;
        }

        $statusSt = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
        $statusMap = [];
        while ($row = $statusSt->fetch()) {
            $statusMap[(string) $row['status']] = (int) $row['cnt'];
        }
        $statusChartData = [
            $statusMap['pending'] ?? 0,
            $statusMap['processing'] ?? 0,
            $statusMap['shipped'] ?? 0,
            $statusMap['delivered'] ?? 0,
            $statusMap['cancelled'] ?? 0,
        ];

        $recent = $pdo->query('SELECT id, order_number, status, customer_name, customer_phone, total, created_at FROM orders ORDER BY created_at DESC LIMIT 10')->fetchAll();

        $topSt = $pdo->query("
            SELECT oi.product_name_snapshot, SUM(oi.qty) as total_qty, SUM(oi.line_total) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') AND o.status != 'cancelled'
            GROUP BY oi.product_name_snapshot
            ORDER BY total_qty DESC
            LIMIT 5
        ");
        $topProducts = $topSt->fetchAll();
    } catch (Throwable) {
    }
}

function trendPct(float $current, float $previous): array
{
    if ($previous == 0) {
        return $current > 0 ? ['up', null] : ['neutral', 0];
    }
    $pct = round((($current - $previous) / $previous) * 100, 1);
    if ($pct > 0) {
        return ['up', $pct];
    } elseif ($pct < 0) {
        return ['down', abs($pct)];
    }
    return ['neutral', 0];
}

require __DIR__ . '/_layout_start.php';
?>

<h1 style="font-size:1.8rem; font-weight:800; margin-bottom:5px;"><?= esc(t('admin_dashboard')) ?></h1>
<p class="admin-lead" style="margin-bottom:30px;">مرحباً بك في لوحة التحكم - نظرة عامة على أداء المتجر</p>

<?php if ($pdo === null): ?>
    <div class="admin-error"><?= esc(t('admin_db_connect_error')) ?></div>
<?php endif; ?>

<div class="admin-stats">
    <?php $tToday = trendPct($stats['today_orders'], $stats['yesterday_orders']); ?>
    <div class="admin-stat">
        <div>
            <strong><?= (int) $stats['today_orders'] ?></strong>
            <span>طلبات اليوم</span>
            <?php if ($tToday[0] === 'up'): ?>
                <small class="trend trend-up">▲ <?= $tToday[1] ?>%</small>
            <?php elseif ($tToday[0] === 'down'): ?>
                <small class="trend trend-down">▼ <?= $tToday[1] ?>%</small>
            <?php endif; ?>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(212,175,55,0.1); color:#d4af37;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        </div>
    </div>

    <?php $tRev = trendPct($stats['monthly_revenue'], $stats['last_month_revenue']); ?>
    <div class="admin-stat">
        <div>
            <strong><?= number_format($stats['monthly_revenue'], $stats['monthly_revenue'] == (int) $stats['monthly_revenue'] ? 0 : 2) ?> <small style="font-size:0.5em;">جنيه</small></strong>
            <span>إيرادات الشهر</span>
            <?php if ($tRev[0] === 'up'): ?>
                <small class="trend trend-up">▲ <?= $tRev[1] ?>%</small>
            <?php elseif ($tRev[0] === 'down'): ?>
                <small class="trend trend-down">▼ <?= $tRev[1] ?>%</small>
            <?php endif; ?>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>

    <div class="admin-stat">
        <div>
            <strong><?= (int) $stats['pending_orders'] ?></strong>
            <span>طلبات معلقة</span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(245,158,11,0.1); color:#f59e0b;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
    </div>

    <div class="admin-stat">
        <div>
            <strong><?= (int) $stats['total_products'] ?></strong>
            <span>إجمالي المنتجات</span>
        </div>
        <div class="admin-stat-icon-box" style="background:rgba(16,185,129,0.1); color:#10b981;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
        </div>
    </div>
</div>

<div class="admin-quick-actions" style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="<?= esc(admin_url('order_management.php')) ?>" class="admin-btn" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        طلب جديد
    </a>
    <a href="<?= esc(admin_url('product_edit.php')) ?>" class="admin-btn admin-btn--secondary" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        إضافة منتج
    </a>
    <a href="<?= esc(admin_url('sales_records.php')) ?>" class="admin-btn admin-btn--secondary" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        عرض التقارير
    </a>
</div>

<div class="admin-charts-row" style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="admin-card" style="padding:24px;">
        <h3 style="margin:0 0 16px; font-size:1.1rem; font-weight:700;">مبيعات آخر 7 أيام</h3>
        <div style="position:relative; height:280px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    <div class="admin-card" style="padding:24px;">
        <h3 style="margin:0 0 16px; font-size:1.1rem; font-weight:700;">توزيع حالة الطلبات</h3>
        <div style="position:relative; height:280px; max-width:280px; margin:0 auto;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<div class="admin-card" style="padding:24px; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:700;">المنتجات الأكثر مبيعاً هذا الشهر</h3>
    </div>
    <?php if ($topProducts === []): ?>
        <p class="admin-muted">لا توجد مبيعات هذا الشهر</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الكمية المباعة</th>
                        <th>الإيرادات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topProducts as $tp): ?>
                        <tr>
                            <td style="font-weight:700;"><?= $rank++ ?></td>
                            <td style="font-weight:600;"><?= esc((string) $tp['product_name_snapshot']) ?></td>
                            <td><?= (int) $tp['total_qty'] ?></td>
                            <td style="font-weight:700;"><?= number_format((float) $tp['total_revenue'], 2) ?> جنيه</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card" style="padding:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:700;">آخر الطلبات</h3>
        <a href="<?= esc(admin_url('orders.php')) ?>" class="admin-btn admin-btn--secondary admin-btn--sm">عرض الكل</a>
    </div>

    <?php if ($recent === []): ?>
        <p class="admin-muted">لا توجد طلبات بعد</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>الهاتف</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr style="cursor:pointer;" onclick="window.location.href='<?= esc(admin_url('order_view.php?id=' . (int) $r['id'])) ?>'">
                            <td style="font-weight:700; color:var(--admin-gold);">#<?= esc((string) $r['order_number']) ?></td>
                            <td style="font-weight:600;"><?= esc((string) $r['customer_name']) ?></td>
                            <td style="direction:ltr; text-align:right;"><?= esc((string) ($r['customer_phone'] ?: '-')) ?></td>
                            <td style="font-weight:800;"><?= number_format((float) $r['total'], 2) ?> <small>جنيه</small></td>
                            <td><span class="admin-badge admin-badge--<?= esc((string) $r['status']) ?>"><?= esc(admin_order_status_label((string) $r['status'])) ?></span></td>
                            <td style="color:#888; font-size:0.9em;"><?= esc((string) $r['created_at']) ?></td>
                            <td>
                                <a href="<?= esc(admin_url('order_view.php?id=' . (int) $r['id'])) ?>"
                                   class="admin-btn admin-btn--sm admin-btn--secondary"
                                   onclick="event.stopPropagation();"
                                   style="white-space:nowrap;">عرض</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($salesChartLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'الإيرادات (جنيه)',
                    data: <?= json_encode($salesChartData) ?>,
                    backgroundColor: '#d4af37',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['قيد الانتظار', 'قيد التجهيز', 'تم الشحن', 'تم التوصيل', 'ملغي'],
                datasets: [{
                    data: <?= json_encode($statusChartData) ?>,
                    backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
})();
</script>

<style>
.trend {
    display: inline-block;
    margin-inline-start: 6px;
    font-size: 0.75em;
    font-weight: 700;
}
.trend-up { color: #10b981; }
.trend-down { color: #ef4444; }

@media (max-width: 768px) {
    .admin-charts-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require __DIR__ . '/_layout_end.php'; ?>