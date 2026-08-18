<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pageTitle = 'سجلات المبيعات';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$where = [];
$params = [];

if ($start_date) {
    $where[] = "o.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}

if ($end_date) {
    $where[] = "o.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = medal_pdo();
$sales = [];
$stats = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];

if ($pdo) {
    $query = "
        SELECT 
            o.id as order_id,
            o.created_at,
            o.total,
            o.status,
            o.customer_name,
            o.customer_phone,
            o.customer_email,
            o.shipping_address,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' (', oi.qty, 'x)') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    $statsQuery = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total) as total_revenue,
            AVG(total) as avg_order_value
        FROM orders o
        $whereClause
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute($params);
    $row = $statsStmt->fetch();
    if ($row) {
        $stats = $row;
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sales_records_' . date('Y-m-d') . '.xls"');
    
    echo "\xEF\xBB\xBF";
    echo "رقم الطلب\tالتاريخ\tاسم العميل\tالهاتف\tالبريد الإلكتروني\tالمنتجات\tالإجمالي\tالحالة\n";
    
    foreach ($sales as $sale) {
        echo $sale['order_id'] . "\t";
        echo date('Y-m-d H:i', strtotime($sale['created_at'])) . "\t";
        echo $sale['customer_name'] . "\t";
        echo $sale['customer_phone'] . "\t";
        echo $sale['customer_email'] . "\t";
        echo $sale['items'] . "\t";
        echo $sale['total'] . "\t";
        echo $sale['status'] . "\n";
    }
    exit;
}

$statusLabels = [
    'pending' => 'قيد الانتظار',
    'processing' => 'قيد التجهيز',
    'shipped' => 'تم الشحن',
    'delivered' => 'تم التوصيل',
    'cancelled' => 'ملغي',
];

require __DIR__ . '/_layout_start.php';
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}
.stat-card:nth-child(2) { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.stat-card:nth-child(3) { background: linear-gradient(135deg, #10b981, #059669); }
.stat-value {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}
.stat-label {
    opacity: 0.9;
    font-size: 0.95em;
}
.filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}
.filter-form {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}
.filter-group {
    flex: 1;
    min-width: 200px;
}
.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.filter-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.btn-filter {
    background: #d4af37;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}
.btn-export {
    background: #28a745;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
}
.btn-export:hover { background: #218838; }
.sales-table {
    width: 100%;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.sales-table table { width: 100%; border-collapse: collapse; }
.sales-table th {
    background: #d4af37;
    color: white;
    padding: 15px;
    text-align: right;
}
.sales-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}
.sales-table tr:hover { background: #f8f9fa; }
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
    font-weight: bold;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-shipped { background: #d1ecf1; color: #0c5460; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }
.customer-info { font-size: 0.9em; color: #666; }
.items-list { max-width: 250px; font-size: 0.9em; }
@media (max-width: 768px) {
    .filter-form { flex-direction: column; }
    .filter-group { width: 100%; }
    .sales-table { font-size: 0.8em; }
    .sales-table th, .sales-table td { padding: 10px 5px; }
}
@media print {
    .filter-section, .admin-nav, .admin-mobile-header { display: none !important; }
    .sales-table { box-shadow: none; }
}
</style>

<div class="admin-header">
    <h1>سجلات المبيعات</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= number_format((int) $stats['total_orders']) ?></div>
        <div class="stat-label">إجمالي الطلبات</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format((float) $stats['total_revenue'], 2) ?> ج.م</div>
        <div class="stat-label">إجمالي الإيرادات</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format((float) $stats['avg_order_value'], 2) ?> ج.م</div>
        <div class="stat-label">متوسط قيمة الطلب</div>
    </div>
</div>

<div class="filter-section">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="start_date">من تاريخ</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="filter-group">
            <label for="end_date">إلى تاريخ</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="filter-group">
            <button type="submit" class="btn-filter">تصفية</button>
        </div>
        <div class="filter-group">
            <a href="?export=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn-export">📥 تصدير إلى إكسل</a>
        </div>
    </form>
</div>

<div class="sales-table">
    <table>
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>التاريخ</th>
                <th>العميل</th>
                <th>المنتجات</th>
                <th>الإجمالي</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td>#<?= (int) $sale['order_id'] ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?></td>
                    <td>
                        <div><strong><?= htmlspecialchars($sale['customer_name']) ?></strong></div>
                        <div class="customer-info">
                            <?= htmlspecialchars($sale['customer_phone']) ?><br>
                            <?= htmlspecialchars($sale['customer_email']) ?>
                        </div>
                    </td>
                    <td>
                        <div class="items-list"><?= htmlspecialchars($sale['items']) ?></div>
                    </td>
                    <td style="font-weight:700;"><?= number_format((float) $sale['total'], 2) ?> ج.م</td>
                    <td>
                        <span class="status-badge status-<?= htmlspecialchars($sale['status']) ?>">
                            <?= htmlspecialchars($statusLabels[$sale['status']] ?? $sale['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($sales)): ?>
    <div style="text-align: center; padding: 50px; color: #666;">
        لا توجد سجلات مبيعات للفترة المحددة
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>