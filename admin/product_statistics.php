<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$pageTitle = 'إحصائيات المنتجات';

$pdo = medal_pdo();
$products = [];
$stats = ['total_products' => 0, 'total_views' => 0, 'avg_views_per_product' => 0, 'total_categories' => 0];

if ($pdo) {
    $query = "
        SELECT 
            p.id,
            p.name_en,
            p.name_ar,
            p.category,
            (SELECT COALESCE(price, 0) FROM product_variants WHERE product_id = p.id ORDER BY sort_order ASC, id ASC LIMIT 1) as price,
            p.view_count,
            (SELECT COUNT(*) FROM order_items oi2 JOIN orders o2 ON oi2.order_id = o2.id WHERE oi2.product_id = p.id AND o2.status = 'delivered') as order_count,
            (SELECT COALESCE(SUM(oi2.qty), 0) FROM order_items oi2 JOIN orders o2 ON oi2.order_id = o2.id WHERE oi2.product_id = p.id AND o2.status = 'delivered') as total_sold,
            (SELECT COALESCE(SUM(oi2.line_total), 0) FROM order_items oi2 JOIN orders o2 ON oi2.order_id = o2.id WHERE oi2.product_id = p.id AND o2.status = 'delivered') as total_revenue,
            p.created_at
        FROM products p
        ORDER BY p.view_count DESC, p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    $statsQuery = "
        SELECT 
            COUNT(*) as total_products,
            SUM(view_count) as total_views,
            COUNT(DISTINCT category) as total_categories,
            AVG(view_count) as avg_views_per_product
        FROM products
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute();
    $row = $statsStmt->fetch();
    if ($row) {
        $stats = $row;
    }
}

$sort = $_GET['sort'] ?? 'views';
$sortedProducts = $products;

switch ($sort) {
    case 'orders':
        usort($sortedProducts, function($a, $b) {
            return $b['order_count'] - $a['order_count'];
        });
        break;
    case 'revenue':
        usort($sortedProducts, function($a, $b) {
            return $b['total_revenue'] - $a['total_revenue'];
        });
        break;
    case 'name':
        usort($sortedProducts, function($a, $b) {
            return strcmp($a['name_en'], $b['name_en']);
        });
        break;
    case 'category':
        usort($sortedProducts, function($a, $b) {
            return strcmp($a['category'], $b['category']);
        });
        break;
    case 'sold':
        usort($sortedProducts, function($a, $b) {
            return $b['total_sold'] - $a['total_sold'];
        });
        break;
}

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
.stat-card:nth-child(4) { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
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
.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.filter-btn {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}
.filter-btn:hover { background: #5a6268; }
.filter-btn.active { background: #d4af37; }
.products-table {
    width: 100%;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.products-table table { width: 100%; border-collapse: collapse; }
.products-table th {
    background: #d4af37;
    color: white;
    padding: 15px;
    text-align: right;
}
.products-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}
.products-table tr:hover { background: #f8f9fa; }
.product-name { font-weight: bold; color: #333; }
.product-category {
    font-size: 0.9em;
    color: #666;
    background: #f0f0f0;
    padding: 3px 8px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 5px;
}
.view-count { font-weight: bold; color: #007bff; font-size: 1.1em; }
.order-count { font-weight: bold; color: #28a745; }
.revenue { font-weight: bold; color: #d4af37; }
.no-views { color: #dc3545; font-size: 0.9em; }
.performance-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-left: 10px;
}
.performance-high { background: #28a745; }
.performance-medium { background: #ffc107; }
.performance-low { background: #dc3545; }
@media (max-width: 768px) {
    .filter-buttons { flex-direction: column; }
    .filter-btn { width: 100%; text-align: center; }
    .products-table { font-size: 0.8em; }
    .products-table th, .products-table td { padding: 10px 5px; }
}
@media print {
    .filter-section, .admin-nav, .admin-mobile-header { display: none !important; }
    .products-table { box-shadow: none; }
}
</style>

<div class="admin-header">
    <h1>إحصائيات المنتجات</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= number_format((int) $stats['total_products']) ?></div>
        <div class="stat-label">إجمالي المنتجات</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format((int) $stats['total_views']) ?></div>
        <div class="stat-label">إجمالي المشاهدات</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format((float) $stats['avg_views_per_product'], 1) ?></div>
        <div class="stat-label">متوسط المشاهدات لكل منتج</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format((int) $stats['total_categories']) ?></div>
        <div class="stat-label">إجمالي الفئات</div>
    </div>
</div>

<div class="filter-section">
    <div class="filter-buttons">
        <a href="?sort=views" class="filter-btn <?= $sort === 'views' ? 'active' : '' ?>">ترتيب حسب المشاهدات</a>
        <a href="?sort=orders" class="filter-btn <?= $sort === 'orders' ? 'active' : '' ?>">ترتيب حسب الطلبات</a>
        <a href="?sort=sold" class="filter-btn <?= $sort === 'sold' ? 'active' : '' ?>">ترتيب حسب الكمية المباعة</a>
        <a href="?sort=revenue" class="filter-btn <?= $sort === 'revenue' ? 'active' : '' ?>">ترتيب حسب الإيرادات</a>
        <a href="?sort=name" class="filter-btn <?= $sort === 'name' ? 'active' : '' ?>">ترتيب أبجدي</a>
        <a href="?sort=category" class="filter-btn <?= $sort === 'category' ? 'active' : '' ?>">ترتيب حسب الفئة</a>
    </div>
</div>

<div class="products-table">
    <table>
        <thead>
            <tr>
                <th>المنتج</th>
                <th>المشاهدات</th>
                <th>عدد الطلبات</th>
                <th>الكمية المباعة</th>
                <th>الإيرادات</th>
                <th>السعر</th>
                <th>تاريخ الإضافة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sortedProducts as $product): 
                $performance = 'low';
                if ($product['view_count'] > 50) $performance = 'high';
                elseif ($product['view_count'] > 10) $performance = 'medium';
            ?>
                <tr>
                    <td>
                        <div class="product-name"><?= htmlspecialchars($product['name_en']) ?></div>
                        <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                    </td>
                    <td>
                        <div class="view-count">
                            <?= number_format((int) $product['view_count']) ?>
                            <span class="performance-indicator performance-<?= $performance ?>"></span>
                        </div>
                        <?php if ((int) $product['view_count'] === 0): ?>
                            <div class="no-views">لا توجد مشاهدات</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="order-count"><?= number_format((int) $product['order_count']) ?></div>
                    </td>
                    <td>
                        <div><?= number_format((int) $product['total_sold']) ?></div>
                    </td>
                    <td>
                        <div class="revenue"><?= number_format((float) $product['total_revenue'], 2) ?> ج.م</div>
                    </td>
                    <td><?= number_format((float) $product['price'], 2) ?> ج.م</td>
                    <td><?= date('Y-m-d', strtotime($product['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($products)): ?>
    <div style="text-align: center; padding: 50px; color: #666;">
        لا توجد منتجات
    </div>
<?php endif; ?>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
    <h3>مؤشرات الأداء</h3>
    <div style="display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap;">
        <div><span class="performance-indicator performance-high"></span> أداء عالي (أكثر من 50 مشاهدة)</div>
        <div><span class="performance-indicator performance-medium"></span> أداء متوسط (10 - 50 مشاهدة)</div>
        <div><span class="performance-indicator performance-low"></span> أداء منخفض (أقل من 10 مشاهدات)</div>
    </div>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>