<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pageTitle = 'التقارير';

$pdo = medal_pdo();
$tab = $_GET['tab'] ?? 'daily';
$validTabs = ['daily', 'monthly', 'products', 'customers', 'promo', 'inventory'];

if (!in_array($tab, $validTabs, true)) {
    $tab = 'daily';
}

$chartData = [];
$reportRows = [];
$summary = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order' => 0];

function getStatusLabel(string $status): string {
    $labels = [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
    ];
    return $labels[$status] ?? $status;
}

function exportToExcel(array $headers, array $rows, string $filename): void {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    echo implode("\t", $headers) . "\n";
    foreach ($rows as $row) {
        echo implode("\t", $row) . "\n";
    }
    exit;
}

if ($pdo) {
    // ==================== TAB 1: Daily Sales ====================
    if ($tab === 'daily') {
        $date = $_GET['date'] ?? date('Y-m-d');

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $st = $pdo->prepare("
                SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.total, o.status, o.created_at,
                       COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE DATE(o.created_at) = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
            ");
            $st->execute([$date]);
            $exportRows = $st->fetchAll();
            $headers = ['رقم الطلب', 'العميل', 'الهاتف', 'عدد المنتجات', 'الإجمالي', 'الحالة', 'التاريخ'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [
                    $r['order_number'],
                    $r['customer_name'],
                    $r['customer_phone'],
                    $r['item_count'],
                    number_format((float) $r['total'], 2),
                    getStatusLabel($r['status']),
                    $r['created_at'],
                ];
            }
            exportToExcel($headers, $data, 'daily_sales_' . $date . '.xls');
        }

        $st = $pdo->prepare("
            SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.total, o.status, o.created_at,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(o.created_at) = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $st->execute([$date]);
        $reportRows = $st->fetchAll();

        $sum = $pdo->prepare("
            SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as rev, COALESCE(AVG(total), 0) as avg_order
            FROM orders WHERE DATE(created_at) = ?
        ");
        $sum->execute([$date]);
        $summary = $sum->fetch() ?: $summary;
        $summary['total_orders'] = (int)($summary['cnt'] ?? 0);
        $summary['total_revenue'] = (float)($summary['rev'] ?? 0);
    }

    // ==================== TAB 2: Monthly Sales ====================
    elseif ($tab === 'monthly') {
        $month = $_GET['month'] ?? date('Y-m');

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $st = $pdo->prepare("
                SELECT DATE(created_at) as day, COUNT(*) as cnt, COALESCE(SUM(total), 0) as rev
                FROM orders
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                GROUP BY DATE(created_at)
                ORDER BY day ASC
            ");
            $st->execute([$month]);
            $exportRows = $st->fetchAll();
            $headers = ['اليوم', 'عدد الطلبات', 'الإيرادات'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [$r['day'], $r['cnt'], number_format((float) $r['rev'], 2)];
            }
            exportToExcel($headers, $data, 'monthly_sales_' . $month . '.xls');
        }

        $st = $pdo->prepare("
            SELECT DATE(created_at) as day, COUNT(*) as cnt, COALESCE(SUM(total), 0) as rev
            FROM orders
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $st->execute([$month]);
        $reportRows = $st->fetchAll();

        $sum = $pdo->prepare("
            SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as rev, COALESCE(AVG(total), 0) as avg_order
            FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $sum->execute([$month]);
        $summary = $sum->fetch() ?: $summary;
        $summary['total_orders'] = $summary['cnt'];
        $summary['total_revenue'] = $summary['rev'];

        $chartData = [
            'labels' => array_map(function($r) { return $r['day']; }, $reportRows),
            'revenue' => array_map(function($r) { return (float) $r['rev']; }, $reportRows),
            'orders' => array_map(function($r) { return (int) $r['cnt']; }, $reportRows),
        ];
    }

    // ==================== TAB 3: Top Products ====================
    elseif ($tab === 'products') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $st = $pdo->prepare("
                SELECT p.name_en, SUM(oi.qty) as qty, SUM(oi.line_total) as rev
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.name_en
                ORDER BY qty DESC
                LIMIT 50
            ");
            $st->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $exportRows = $st->fetchAll();
            $headers = ['المنتج', 'الكمية المباعة', 'الإيرادات'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [$r['name_en'], $r['qty'], number_format((float) $r['rev'], 2)];
            }
            exportToExcel($headers, $data, 'top_products_' . date('Y-m-d') . '.xls');
        }

        $st = $pdo->prepare("
            SELECT p.name_en, SUM(oi.qty) as qty, SUM(oi.line_total) as rev
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id, p.name_en
            ORDER BY qty DESC
            LIMIT 50
        ");
        $st->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $reportRows = $st->fetchAll();

        $totalRev = array_sum(array_column($reportRows, 'rev'));

        $chartData = [
            'labels' => array_map(function($r) { return $r['name_en']; }, array_slice($reportRows, 0, 10)),
            'quantities' => array_map(function($r) { return (int) $r['qty']; }, array_slice($reportRows, 0, 10)),
            'totalRev' => $totalRev,
        ];
    }

    // ==================== TAB 4: Customers Report ====================
    elseif ($tab === 'customers') {
        $custFilter = $_GET['cust_filter'] ?? 'all';
        $sortCol = $_GET['sort_col'] ?? 'total_spent';
        $sortDir = $_GET['sort_dir'] ?? 'desc';
        $validSortCols = ['name', 'orders_count', 'total_spent', 'last_order'];
        if (!in_array($sortCol, $validSortCols, true)) $sortCol = 'total_spent';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $sql = "
                SELECT c.name, c.email, c.phone,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as orders_count,
                       (SELECT COALESCE(SUM(total), 0) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as total_spent,
                       (SELECT MAX(created_at) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as last_order
                FROM clients c
                HAVING orders_count > 0
                ORDER BY total_spent DESC
            ";
            $exportRows = $pdo->query($sql)->fetchAll();
            $headers = ['الاسم', 'البريد', 'الهاتف', 'عدد الطلبات', 'الإجمالي', 'آخر طلب'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [$r['name'], $r['email'], $r['phone'], $r['orders_count'], number_format((float) $r['total_spent'], 2), $r['last_order']];
            }
            exportToExcel($headers, $data, 'customers_report_' . date('Y-m-d') . '.xls');
        }

        $whereExtra = '';
        if ($custFilter === 'repeat') {
            $whereExtra = 'HAVING orders_count > 1';
        } elseif ($custFilter === 'new') {
            $whereExtra = 'HAVING orders_count = 1';
        } elseif ($custFilter === 'top') {
            $whereExtra = 'HAVING total_spent > 0 ORDER BY total_spent DESC LIMIT 50';
        }

        $sql = "
            SELECT c.name, c.email, c.phone,
                   (SELECT COUNT(*) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as orders_count,
                   (SELECT COALESCE(SUM(total), 0) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as total_spent,
                   (SELECT MAX(created_at) FROM orders o WHERE o.customer_phone = c.phone OR o.customer_email = c.email) as last_order
            FROM clients c
            $whereExtra
        ";

        if ($custFilter !== 'top') {
            $orderMap = [
                'name' => 'c.name',
                'orders_count' => 'orders_count',
                'total_spent' => 'total_spent',
                'last_order' => 'last_order',
            ];
            $sql .= " ORDER BY {$orderMap[$sortCol]} $sortDir";
        }

        $reportRows = $pdo->query($sql)->fetchAll();
    }

    // ==================== TAB 5: Promo Codes Report ====================
    elseif ($tab === 'promo') {
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $exportRows = $pdo->query("
                SELECT code, usage_count, usage_limit, total_discount,
                       CASE WHEN usage_limit > 0 THEN ROUND(usage_count / usage_limit * 100, 1) ELSE 0 END as pct_used
                FROM promo_codes
                ORDER BY usage_count DESC
            ")->fetchAll();
            $headers = ['الكود', 'عدد الاستخدامات', 'الحد الأقصى', 'إجمالي الخصم', 'نسبة الاستخدام %'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [$r['code'], $r['usage_count'], $r['usage_limit'], number_format((float) $r['total_discount'], 2), $r['pct_used'] . '%'];
            }
            exportToExcel($headers, $data, 'promo_codes_report_' . date('Y-m-d') . '.xls');
        }

        $reportRows = $pdo->query("
            SELECT code, usage_count, usage_limit, total_discount,
                   CASE WHEN usage_limit > 0 THEN ROUND(usage_count / usage_limit * 100, 1) ELSE 0 END as pct_used
            FROM promo_codes
            ORDER BY usage_count DESC
        ")->fetchAll();
    }

    // ==================== TAB 6: Inventory Report ====================
    elseif ($tab === 'inventory') {
        $invFilter = $_GET['inv_filter'] ?? 'all';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $exportRows = $pdo->query("
                SELECT p.name_en, COALESCE(pv.size, 'افتراضي') as variant, pv.stock,
                       CASE WHEN pv.stock = 0 THEN 'نفذ المخزون' WHEN pv.stock <= 5 THEN 'مخزون منخفض' ELSE 'متوفر' END as warning
                FROM products p
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                ORDER BY pv.stock ASC
            ")->fetchAll();
            $headers = ['المنتج', 'المتغير', 'المخزون', 'تحذير'];
            $data = [];
            foreach ($exportRows as $r) {
                $data[] = [$r['name_en'], $r['variant'], $r['stock'], $r['warning']];
            }
            exportToExcel($headers, $data, 'inventory_report_' . date('Y-m-d') . '.xls');
        }

        $whereSQL = '';
        if ($invFilter === 'low') {
            $whereSQL = 'WHERE pv.stock > 0 AND pv.stock <= 5';
        } elseif ($invFilter === 'out') {
            $whereSQL = 'WHERE pv.stock = 0';
        }

        $reportRows = $pdo->query("
            SELECT p.name_en, COALESCE(pv.size, 'افتراضي') as variant, pv.stock,
                   CASE WHEN pv.stock = 0 THEN 'نفذ المخزون' WHEN pv.stock <= 5 THEN 'مخزون منخفض' ELSE 'متوفر' END as warning
            FROM products p
            LEFT JOIN product_variants pv ON p.id = pv.product_id
            $whereSQL
            ORDER BY pv.stock ASC
        ")->fetchAll();
    }
}

// Helper for sort links
function sortLink(string $col, string $currentCol, string $currentDir, string $tab, array $extraParams = []): string {
    $dir = ($col === $currentCol && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = array_merge(['tab' => $tab, 'sort_col' => $col, 'sort_dir' => $dir], $extraParams);
    return '?' . http_build_query($params);
}

function sortArrow(string $col, string $currentCol, string $currentDir): string {
    if ($col !== $currentCol) return '';
    return $currentDir === 'asc' ? ' ▲' : ' ▼';
}

require __DIR__ . '/_layout_start.php';
?>

<style>
.reports-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    background: rgba(212,175,55,0.05);
    padding: .5rem;
    border-radius: 12px;
    border: 1px solid var(--admin-card-border);
}
.reports-tab {
    text-decoration: none;
    padding: .5rem 1rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: .9rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    transition: all .2s;
    color: var(--admin-text-muted);
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-card-border);
}
.reports-tab.active {
    background: var(--admin-btn-bg);
    color: var(--admin-btn-text);
    box-shadow: var(--admin-shadow-sm);
}
.reports-section {
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-card-border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.reports-section h3 {
    margin: 0 0 1rem;
    font-size: 1.1rem;
}
.filter-row {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.filter-row label {
    display: block;
    margin-bottom: 0.3rem;
    font-weight: 600;
    font-size: 0.85rem;
}
.filter-row input, .filter-row select {
    padding: .55rem .85rem;
    border: 1px solid var(--admin-input-border);
    border-radius: 6px;
    background: var(--admin-card-bg);
    font-size: .9rem;
    min-width: 150px;
}
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.summary-card {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #fff;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}
.summary-card:nth-child(2) { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.summary-card:nth-child(3) { background: linear-gradient(135deg, #10b981, #059669); }
.summary-card .val { font-size: 1.6em; font-weight: bold; }
.summary-card .lbl { opacity: 0.9; font-size: 0.85rem; }
.report-table {
    width: 100%;
    border-collapse: collapse;
}
.report-table th {
    background: #d4af37;
    color: #fff;
    padding: 12px;
    text-align: right;
    font-size: 0.9rem;
}
.report-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}
.report-table tr:hover { background: #f8f9fa; }
.chart-container {
    margin: 1.5rem 0;
    max-width: 100%;
    height: 350px;
}
.btn-sm {
    padding: .4rem .8rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: .85rem;
    text-decoration: none;
    display: inline-block;
}
.btn-primary { background: #d4af37; color: #fff; }
.btn-success { background: #28a745; color: #fff; }
.btn-success:hover { background: #218838; }
.warning-out { color: #dc3545; font-weight: bold; }
.warning-low { color: #ffc107; font-weight: bold; }
.warning-ok { color: #28a745; }
@media (max-width: 768px) {
    .reports-tabs { flex-direction: column; }
    .filter-row { flex-direction: column; }
    .filter-row input, .filter-row select { width: 100%; }
}
@media print {
    .reports-tabs, .admin-nav, .admin-mobile-header, .filter-row, .btn-sm { display: none !important; }
    .reports-section { border: none; box-shadow: none; }
}
</style>

<div class="admin-header">
    <h1>التقارير</h1>
</div>

<div class="reports-tabs">
    <?php
    $tabs = [
        'daily' => '📅 تقرير المبيعات اليومي',
        'monthly' => '📊 تقرير المبيعات الشهري',
        'products' => '🏆 المنتجات الأكثر مبيعاً',
        'customers' => '👥 تقرير العملاء',
        'promo' => '🎫 تقرير أكواد الخصم',
        'inventory' => '📦 تقرير المخزون',
    ];
    foreach ($tabs as $key => $label):
        $isActive = $tab === $key;
    ?>
        <a href="?tab=<?= $key ?>" class="reports-tab <?= $isActive ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php
// ==================== TAB 1: Daily Sales ====================
if ($tab === 'daily'):
?>
<div class="reports-section">
    <h3>تقرير المبيعات اليومي</h3>
    <div class="filter-row">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="daily">
            <div>
                <label>اختر التاريخ</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
            </div>
            <button type="submit" class="btn-sm btn-primary">عرض</button>
            <a href="?tab=daily&export=excel&date=<?= urlencode($date) ?>" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="val"><?= number_format((int) $summary['total_orders']) ?></div>
            <div class="lbl">إجمالي الطلبات</div>
        </div>
        <div class="summary-card">
            <div class="val"><?= number_format((float) $summary['total_revenue'], 2) ?> ج.م</div>
            <div class="lbl">إجمالي الإيرادات</div>
        </div>
        <div class="summary-card">
            <div class="val"><?= number_format((float) $summary['avg_order'], 2) ?> ج.م</div>
            <div class="lbl">متوسط قيمة الطلب</div>
        </div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>عدد المنتجات</th>
                <th>الإجمالي</th>
                <th>الحالة</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['order_number']) ?></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['customer_phone']) ?></td>
                <td><?= (int) $r['item_count'] ?></td>
                <td style="font-weight:700;"><?= number_format((float) $r['total'], 2) ?> ج.م</td>
                <td><?= getStatusLabel($r['status']) ?></td>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="7" style="text-align:center; padding:2rem;">لا توجد طلبات لهذا التاريخ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// ==================== TAB 2: Monthly Sales ====================
elseif ($tab === 'monthly'):
?>
<div class="reports-section">
    <h3>تقرير المبيعات الشهري</h3>
    <div class="filter-row">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="monthly">
            <div>
                <label>اختر الشهر</label>
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
            </div>
            <button type="submit" class="btn-sm btn-primary">عرض</button>
            <a href="?tab=monthly&export=excel&month=<?= urlencode($month) ?>" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="val"><?= number_format((int) $summary['total_orders']) ?></div>
            <div class="lbl">إجمالي الطلبات</div>
        </div>
        <div class="summary-card">
            <div class="val"><?= number_format((float) $summary['total_revenue'], 2) ?> ج.م</div>
            <div class="lbl">إجمالي الإيرادات</div>
        </div>
        <div class="summary-card">
            <div class="val"><?= number_format((float) $summary['avg_order'], 2) ?> ج.م</div>
            <div class="lbl">متوسط قيمة الطلب</div>
        </div>
    </div>

    <?php if (!empty($chartData['labels'])): ?>
    <div class="chart-container">
        <canvas id="monthlyChart"></canvas>
    </div>
    <?php endif; ?>

    <table class="report-table">
        <thead>
            <tr>
                <th>اليوم</th>
                <th>عدد الطلبات</th>
                <th>الإيرادات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['day']) ?></td>
                <td><?= (int) $r['cnt'] ?></td>
                <td style="font-weight:700;"><?= number_format((float) $r['rev'], 2) ?> ج.م</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="3" style="text-align:center; padding:2rem;">لا توجد بيانات لهذا الشهر</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// ==================== TAB 3: Top Products ====================
elseif ($tab === 'products'):
?>
<div class="reports-section">
    <h3>المنتجات الأكثر مبيعاً</h3>
    <div class="filter-row">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="products">
            <div>
                <label>من تاريخ</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div>
                <label>إلى تاريخ</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <button type="submit" class="btn-sm btn-primary">عرض</button>
            <a href="?tab=products&export=excel&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
        </form>
    </div>

    <?php if (!empty($chartData['labels'])): ?>
    <div class="chart-container">
        <canvas id="productsChart"></canvas>
    </div>
    <?php endif; ?>

    <table class="report-table">
        <thead>
            <tr>
                <th>المنتج</th>
                <th>الكمية المباعة</th>
                <th>الإيرادات</th>
                <th>النسبة من الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): 
                $pct = $chartData['totalRev'] > 0 ? round((float) $r['rev'] / $chartData['totalRev'] * 100, 1) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($r['name_en']) ?></td>
                <td><?= (int) $r['qty'] ?></td>
                <td style="font-weight:700;"><?= number_format((float) $r['rev'], 2) ?> ج.م</td>
                <td><?= $pct ?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="4" style="text-align:center; padding:2rem;">لا توجد بيانات للفترة المحددة</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// ==================== TAB 4: Customers Report ====================
elseif ($tab === 'customers'):
?>
<div class="reports-section">
    <h3>تقرير العملاء</h3>
    <div class="filter-row">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="customers">
            <input type="hidden" name="sort_col" value="<?= htmlspecialchars($sortCol) ?>">
            <input type="hidden" name="sort_dir" value="<?= htmlspecialchars($sortDir) ?>">
            <div>
                <label>تصنيف العملاء</label>
                <select name="cust_filter">
                    <option value="all" <?= $custFilter === 'all' ? 'selected' : '' ?>>جميع العملاء</option>
                    <option value="new" <?= $custFilter === 'new' ? 'selected' : '' ?>>عملاء جدد (طلب واحد)</option>
                    <option value="repeat" <?= $custFilter === 'repeat' ? 'selected' : '' ?>>عملاء متكررين</option>
                    <option value="top" <?= $custFilter === 'top' ? 'selected' : '' ?>>أعلى المنفقين</option>
                </select>
            </div>
            <button type="submit" class="btn-sm btn-primary">تصفية</button>
            <a href="?tab=customers&export=excel&cust_filter=<?= urlencode($custFilter) ?>" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
        </form>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>
                    <a href="<?= sortLink('name', $sortCol, $sortDir, 'customers', ['cust_filter' => $custFilter]) ?>" style="color:#fff; text-decoration:none;">
                        الاسم<?= sortArrow('name', $sortCol, $sortDir) ?>
                    </a>
                </th>
                <th>البريد الإلكتروني</th>
                <th>الهاتف</th>
                <th>
                    <a href="<?= sortLink('orders_count', $sortCol, $sortDir, 'customers', ['cust_filter' => $custFilter]) ?>" style="color:#fff; text-decoration:none;">
                        عدد الطلبات<?= sortArrow('orders_count', $sortCol, $sortDir) ?>
                    </a>
                </th>
                <th>
                    <a href="<?= sortLink('total_spent', $sortCol, $sortDir, 'customers', ['cust_filter' => $custFilter]) ?>" style="color:#fff; text-decoration:none;">
                        إجمالي الإنفاق<?= sortArrow('total_spent', $sortCol, $sortDir) ?>
                    </a>
                </th>
                <th>
                    <a href="<?= sortLink('last_order', $sortCol, $sortDir, 'customers', ['cust_filter' => $custFilter]) ?>" style="color:#fff; text-decoration:none;">
                        آخر طلب<?= sortArrow('last_order', $sortCol, $sortDir) ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['phone'] ?: '-') ?></td>
                <td><?= (int) $r['orders_count'] ?></td>
                <td style="font-weight:700;"><?= number_format((float) $r['total_spent'], 2) ?> ج.م</td>
                <td><?= htmlspecialchars($r['last_order'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="6" style="text-align:center; padding:2rem;">لا يوجد عملاء</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// ==================== TAB 5: Promo Codes Report ====================
elseif ($tab === 'promo'):
?>
<div class="reports-section">
    <h3>تقرير أكواد الخصم</h3>
    <div class="filter-row">
        <a href="?tab=promo&export=excel" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>الكود</th>
                <th>عدد الاستخدامات</th>
                <th>الحد الأقصى للاستخدام</th>
                <th>إجمالي الخصم الممنوح</th>
                <th>نسبة الاستخدام</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['code']) ?></strong></td>
                <td><?= (int) $r['usage_count'] ?></td>
                <td><?= (int) $r['usage_limit'] > 0 ? (int) $r['usage_limit'] : 'غير محدود' ?></td>
                <td style="font-weight:700;"><?= number_format((float) $r['total_discount'], 2) ?> ج.م</td>
                <td>
                    <?= $r['pct_used'] ?>%
                    <?php if ((float) $r['pct_used'] >= 80): ?>
                        <span class="warning-out">(قارب على النفاد)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="5" style="text-align:center; padding:2rem;">لا توجد أكواد خصم</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// ==================== TAB 6: Inventory Report ====================
elseif ($tab === 'inventory'):
?>
<div class="reports-section">
    <h3>تقرير المخزون</h3>
    <div class="filter-row">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="inventory">
            <div>
                <label>حالة المخزون</label>
                <select name="inv_filter">
                    <option value="all" <?= $invFilter === 'all' ? 'selected' : '' ?>>جميع المنتجات</option>
                    <option value="low" <?= $invFilter === 'low' ? 'selected' : '' ?>>مخزون منخفض فقط</option>
                    <option value="out" <?= $invFilter === 'out' ? 'selected' : '' ?>>نفذ المخزون فقط</option>
                </select>
            </div>
            <button type="submit" class="btn-sm btn-primary">تصفية</button>
            <a href="?tab=inventory&export=excel&inv_filter=<?= urlencode($invFilter) ?>" class="btn-sm btn-success">📥 تصدير إلى إكسل</a>
        </form>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>المنتج</th>
                <th>المتغير</th>
                <th>المخزون</th>
                <th>تحذير</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['name_en']) ?></strong></td>
                <td><?= htmlspecialchars($r['variant']) ?></td>
                <td><?= (int) $r['stock'] ?></td>
                <td>
                    <?php if ((int) $r['stock'] === 0): ?>
                        <span class="warning-out">نفذ المخزون</span>
                    <?php elseif ((int) $r['stock'] <= 5): ?>
                        <span class="warning-low">مخزون منخفض</span>
                    <?php else: ?>
                        <span class="warning-ok">متوفر</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reportRows)): ?>
            <tr><td colspan="4" style="text-align:center; padding:2rem;">لا توجد منتجات تطابق المعايير</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    <?php if ($tab === 'monthly' && !empty($chartData['labels'])): ?>
    var ctx = document.getElementById('monthlyChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartData['labels']) ?>,
                datasets: [{
                    label: 'الإيرادات (ج.م)',
                    data: <?= json_encode($chartData['revenue']) ?>,
                    borderColor: '#d4af37',
                    backgroundColor: 'rgba(212,175,55,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    yAxisID: 'y'
                }, {
                    label: 'عدد الطلبات',
                    data: <?= json_encode($chartData['orders']) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'الإيرادات (ج.م)' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'عدد الطلبات' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if ($tab === 'products' && !empty($chartData['labels'])): ?>
    var pctx = document.getElementById('productsChart');
    if (pctx) {
        new Chart(pctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartData['labels']) ?>,
                datasets: [{
                    label: 'الكمية المباعة',
                    data: <?= json_encode($chartData['quantities']) ?>,
                    backgroundColor: 'rgba(212,175,55,0.7)',
                    borderColor: '#d4af37',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'الكمية المباعة' }
                    }
                }
            }
        });
    }
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>