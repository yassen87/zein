<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';

$pdo = medal_pdo();
if (!$pdo) die('DB not connected');

$pdo->exec("CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT '',
    is_superadmin TINYINT(1) NOT NULL DEFAULT 0,
    permissions TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
if ($count == 0) {
    $roles = [
        ['مدير عام', 'صلاحيات كاملة - جميع الصفحات', 1, ''],
        ['مدير مبيعات', 'إدارة الطلبات والعملاء والتقارير', 0, 'orders,order_management,orders_export,clients,students,client_statement,clients_export,reports,sales_records,product_statistics,notifications'],
        ['مدير محتوى', 'إدارة المنتجات والعروض والماركات', 0, 'products,offers,brands,internal_products,reviews,categories,promo_codes'],
        ['خدمة عملاء', 'الرد على الرسائل ومتابعة الطلبات', 0, 'orders,order_management,messages,notifications,clients'],
        ['موظف شحن', 'إدارة الشحن والطلبات', 0, 'orders,shipping,notifications'],
    ];
    $st = $pdo->prepare("INSERT INTO roles (name, description, is_superadmin, permissions) VALUES (?, ?, ?, ?)");
    foreach ($roles as $r) {
        $st->execute($r);
    }
}

try {
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN role_id INT UNSIGNED DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists
}

echo "Roles table created and seeded successfully!\n";