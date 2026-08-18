<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Schema Update - Meda Admin Features</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d4af37; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-right: 4px solid #d4af37; background: #f9f9f9; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Schema Update - Admin Features</h1>";

$pdo = medal_pdo();

if (!$pdo) {
    echo "<div class='error'>Database connection failed!</div>";
    exit;
}

echo "<div class='step'>
    <h3>Step 1: Adding view_count to products table</h3>";

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER is_offer");
    echo "<div class='success'>View count column added to products table!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>View count column already exists or error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Step 2: Creating internal_products table</h3>";

try {
    $createInternalProducts = "
        CREATE TABLE IF NOT EXISTS internal_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name_en VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255) NOT NULL,
            description TEXT,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            type ENUM('gift', 'sample', 'promotional') NOT NULL DEFAULT 'gift',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createInternalProducts);
    echo "<div class='success'>Internal products table created!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Error creating internal_products table: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Step 3: Creating order_internal_products table</h3>";

try {
    $createOrderInternalProducts = "
        CREATE TABLE IF NOT EXISTS order_internal_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            internal_product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (internal_product_id) REFERENCES internal_products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createOrderInternalProducts);
    echo "<div class='success'>Order internal products table created!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>Error creating order_internal_products table: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Step 4: Adding file_sharing_url to products table</h3>";

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
    echo "<div class='success'>File sharing URL column added to products table!</div>";
} catch (PDOException $e) {
    echo "<div class='info'>File sharing URL column already exists or error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
    <h3>Step 5: Creating sample internal products</h3>";

$sampleProducts = [
    [
        'name_en' => 'Welcome Gift Box',
        'name_ar' => ' puzzles',
        'description' => 'A special gift box for new customers with sample products',
        'cost' => 15.00,
        'type' => 'gift'
    ],
    [
        'name_en' => 'Perfume Sample Set',
        'name_ar' => ' puzzles',
        'description' => 'Collection of perfume samples for customers to try',
        'cost' => 5.00,
        'type' => 'sample'
    ],
    [
        'name_en' => 'Loyalty Reward',
        'name_ar' => ' puzzles',
        'description' => 'Special reward for loyal customers',
        'cost' => 25.00,
        'type' => 'promotional'
    ]
];

foreach ($sampleProducts as $product) {
    try {
        $check = $pdo->prepare("SELECT id FROM internal_products WHERE name_en = ?");
        $check->execute([$product['name_en']]);
        if ($check->fetch() === false) {
            $insert = $pdo->prepare("
                INSERT INTO internal_products (name_en, name_ar, description, cost, type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $product['name_en'],
                $product['name_ar'],
                $product['description'],
                $product['cost'],
                $product['type']
            ]);
            echo "<div class='success'>Sample internal product created: " . htmlspecialchars($product['name_en']) . "</div>";
        } else {
            echo "<div class='info'>Sample internal product already exists: " . htmlspecialchars($product['name_en']) . "</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error creating sample product: " . $e->getMessage() . "</div>";
    }
}

echo "</div>";

echo "<div class='step'>
    <h3>Step 6: Updating admin navigation</h3>";

echo "<div class='info'>New admin pages created:</div>";
echo "<ul>";
echo "<li><strong>sales_records.php</strong> - Sales records with customer details and Excel export</li>";
echo "<li><strong>product_statistics.php</strong> - Product view statistics and analytics</li>";
echo "<li><strong>order_management.php</strong> - Order management with editing capabilities</li>";
echo "<li><strong>internal_products.php</strong> - Internal products management for gifts</li>";
echo "</ul>";

echo "</div>";

echo "<div class='step'>
    <h3>Step 7: Verification</h3>";

// Check all tables
$tables = ['products', 'internal_products', 'order_internal_products', 'orders', 'order_items'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        echo "<div class='success'>Table '$table' exists with " . count($columns) . " columns</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Table '$table' not found: " . $e->getMessage() . "</div>";
    }
}

echo "</div>";

echo "<div class='step'>
    <h3>Setup Complete!</h3>";

echo "<div class='success'>
        <h4>Database schema updated successfully!</h4>
        <p>New admin features are now available:</p>
        <ul>
            <li><a href='../admin/sales_records.php'>Sales Records</a> - View customer sales and export to Excel</li>
            <li><a href='../admin/product_statistics.php'>Product Statistics</a> - Track product views and performance</li>
            <li><a href='../admin/order_management.php'>Order Management</a> - Edit orders and customer details</li>
            <li><a href='../admin/internal_products.php'>Internal Products</a> - Manage gifts and promotional items</li>
        </ul>
        <p><strong>Next steps:</strong></p>
        <ul>
            <li>Update admin navigation to include new pages</li>
            <li>Test all new features</li>
            <li>Train staff on new functionality</li>
        </ul>
    </div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
