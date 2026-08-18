<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function pdo(bool $withDatabase = true): PDO
{
    static $db = null;
    static $server = null;

    if ($withDatabase && $db instanceof PDO) {
        return $db;
    }
    if (!$withDatabase && $server instanceof PDO) {
        return $server;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ($withDatabase ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if ($withDatabase) {
        $db = $pdo;
    } else {
        $server = $pdo;
    }

    return $pdo;
}

function database_exists(): bool
{
    try {
        pdo(true)->query('SELECT 1 FROM users LIMIT 1');
        ensure_schema_updates();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_schema_updates(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $db = pdo(true);
    $updates = [
        "ALTER TABLE locations ADD COLUMN qr_code VARCHAR(120) NULL",
        "ALTER TABLE locations ADD COLUMN latitude DECIMAL(10,7) NULL",
        "ALTER TABLE locations ADD COLUMN longitude DECIMAL(10,7) NULL",
        "ALTER TABLE locations ADD COLUMN geo_radius_m INT NOT NULL DEFAULT 100",
        "ALTER TABLE products ADD COLUMN min_stock DECIMAL(14,3) NOT NULL DEFAULT 0",
        "ALTER TABLE online_orders ADD COLUMN discount_type ENUM('amount','percent') NULL",
        "ALTER TABLE online_orders ADD COLUMN discount_value DECIMAL(12,2) NOT NULL DEFAULT 0",
        "ALTER TABLE online_orders ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0",
        "ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL",
        "CREATE TABLE IF NOT EXISTS role_permissions (role_id INT NOT NULL, permission_code VARCHAR(80) NOT NULL, PRIMARY KEY (role_id, permission_code), CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "ALTER TABLE users ADD COLUMN basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00",
        "ALTER TABLE users ADD COLUMN commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00",
        "ALTER TABLE customers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
        "ALTER TABLE customers ADD COLUMN birthdate DATE NULL",
        "ALTER TABLE formula_defaults ADD COLUMN default_grams DECIMAL(12,3) NOT NULL DEFAULT 0",
        "ALTER TABLE formula_defaults ADD COLUMN bottle_product_id INT NULL",
        "ALTER TABLE inventory_transfers ADD COLUMN sender_name VARCHAR(120) NULL",
        "ALTER TABLE inventory_transfers ADD COLUMN receiver_name VARCHAR(120) NULL",
        "ALTER TABLE inventory_transfers ADD COLUMN transfer_date DATE NOT NULL DEFAULT CURRENT_DATE",
        "ALTER TABLE inventory_movements MODIFY COLUMN movement_type ENUM('initial','sale','manual_adjustment','transfer_future','transfer_adjust','return_future') NOT NULL",
        "CREATE TABLE IF NOT EXISTS wasted_products (id INT AUTO_INCREMENT PRIMARY KEY, location_id INT NOT NULL, product_id INT NOT NULL, quantity DECIMAL(14,3) NOT NULL, reason VARCHAR(255) NULL, created_by INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_waste_location FOREIGN KEY (location_id) REFERENCES locations(id), CONSTRAINT fk_waste_product FOREIGN KEY (product_id) REFERENCES products(id), CONSTRAINT fk_waste_user FOREIGN KEY (created_by) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    // Migration: branch_targets from target_month to target_date
    try {
        $stmt = $db->query("SHOW COLUMNS FROM branch_targets LIKE 'target_month'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE branch_targets CHANGE COLUMN target_month target_date DATE NOT NULL");
            // Drop old index (try multiple syntaxes for compatibility)
            try { $db->exec("DROP INDEX uq_target_month ON branch_targets"); } catch (Throwable $ex) {}
            try { $db->exec("DROP INDEX IF EXISTS uq_target_month ON branch_targets"); } catch (Throwable $ex) {}
            // Add new unique index
            try { $db->exec("ALTER TABLE branch_targets ADD UNIQUE INDEX uq_target_date (location_id, target_date)"); } catch (Throwable $ex) {}
            // Convert existing month data to first day of month
            $db->exec("UPDATE branch_targets SET target_date = STR_TO_DATE(CONCAT(target_date, '-01'), '%Y-%m-%d')");
        }
    } catch (Throwable $e) {
    }

    // Migration: add bottle_product_id to formula_defaults
    try {
        $stmt = $db->query("SHOW COLUMNS FROM formula_defaults LIKE 'bottle_product_id'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE formula_defaults ADD COLUMN bottle_product_id INT NULL AFTER id");
        }
    } catch (Throwable $e) {
    }

    foreach ($updates as $sql) {
        try {
            $db->exec($sql);
        } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), 'Duplicate column') && !str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }

    try {
        $db->exec("DELETE f1 FROM formula_defaults f1 INNER JOIN formula_defaults f2 ON f1.perfume_family = f2.perfume_family AND COALESCE(f1.quality_grade, '') = COALESCE(f2.quality_grade, '') AND f1.bottle_size_ml = f2.bottle_size_ml WHERE f1.id > f2.id");
        $db->exec("UPDATE formula_defaults SET quality_grade = '' WHERE quality_grade IS NULL");
        $db->exec("ALTER TABLE formula_defaults MODIFY COLUMN quality_grade VARCHAR(10) NOT NULL DEFAULT ''");
    } catch (Throwable $e) {
        // Safe fallback if table doesn't exist yet or columns don't match
    }

    try {
        $column = $db->query("SHOW COLUMNS FROM formula_defaults LIKE 'default_grams'")->fetch();
        if ($column) {
            $db->exec("DELETE FROM formula_defaults WHERE (perfume_family = 'oriental' AND quality_grade = 'A' AND bottle_size_ml = 50 AND default_grams = 12.000) OR (perfume_family = 'oriental' AND quality_grade = 'A+' AND bottle_size_ml = 50 AND default_grams = 13.000) OR (perfume_family = 'french' AND quality_grade = '' AND bottle_size_ml = 50 AND default_grams = 15.000) OR (perfume_family = 'oriental' AND quality_grade = 'A' AND bottle_size_ml = 100 AND default_grams = 24.000) OR (perfume_family = 'french' AND quality_grade = '' AND bottle_size_ml = 100 AND default_grams = 30.000)");
        }
    } catch (Throwable $e) {
        // Safe fallback if table doesn't exist yet or query fails
    }

    try {
        $count = (int) $db->query('SELECT COUNT(*) FROM role_permissions')->fetchColumn();
        if ($count === 0) {
            $roles_by_code = [];
            foreach ($db->query('SELECT id, code FROM roles')->fetchAll() as $r) {
                $roles_by_code[$r['code']] = (int) $r['id'];
            }
            
            $default_perms = [
                'admin' => [
                    'pos', 'invoices', 'invoices_notes', 'returns',
                    'products_view', 'products_add', 'products_edit', 'products_delete',
                    'recipes_view', 'recipes_add', 'recipes_edit',
                    'inventory_view', 'inventory_adjust', 'transfers',
                    'customers_view', 'customers_add', 'customers_edit', 'customers_pay_debt',
                    'users_view', 'users_add', 'users_permissions',
                    'attendance', 'shifts', 'targets',
                    'expenses_view', 'expenses_add',
                    'suppliers_view', 'suppliers_add',
                    'reports_sales_by_location', 'reports_sales_by_payment', 'reports_sales_by_user', 
                    'reports_top_products', 'reports_perfume_usage', 'reports_new_customers', 
                    'online_orders', 'backup', 'settings', 'audit'
                ],
                'branch_manager' => [
                    'pos', 'invoices', 'invoices_notes', 'returns',
                    'inventory_view', 'inventory_adjust', 'transfers',
                    'customers_view', 'customers_add', 'customers_edit', 'customers_pay_debt',
                    'attendance', 'shifts', 'targets', 'online_orders',
                    'reports_sales_by_location', 'reports_sales_by_payment', 'reports_new_customers'
                ],
                'cashier' => [
                    'pos', 'attendance', 'shifts', 'invoices'
                ],
                'warehouse_keeper' => [
                    'products_view', 'products_add', 'products_edit', 'products_delete',
                    'inventory_view', 'inventory_adjust', 'transfers',
                    'suppliers_view', 'suppliers_add', 'attendance'
                ]
            ];
            
            $stmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_code) VALUES (?, ?)');
            foreach ($default_perms as $role_code => $perms) {
                if (isset($roles_by_code[$role_code])) {
                    $role_id = $roles_by_code[$role_code];
                    foreach ($perms as $perm) {
                        $stmt->execute([$role_id, $perm]);
                    }
                }
            }
        } else {
            // Run dynamic migration of old permissions to new granular ones if any exist
            $migrations = [
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'products_view' FROM role_permissions WHERE permission_code = 'products'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'products_add' FROM role_permissions WHERE permission_code = 'products'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'products_edit' FROM role_permissions WHERE permission_code = 'products'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'products_delete' FROM role_permissions WHERE permission_code = 'products'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'recipes_view' FROM role_permissions WHERE permission_code = 'recipes'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'recipes_add' FROM role_permissions WHERE permission_code = 'recipes'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'recipes_edit' FROM role_permissions WHERE permission_code = 'recipes'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'inventory_view' FROM role_permissions WHERE permission_code = 'inventory'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'inventory_adjust' FROM role_permissions WHERE permission_code = 'inventory'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'customers_view' FROM role_permissions WHERE permission_code = 'customers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'customers_add' FROM role_permissions WHERE permission_code = 'customers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'customers_edit' FROM role_permissions WHERE permission_code = 'customers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'customers_pay_debt' FROM role_permissions WHERE permission_code = 'customers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'users_view' FROM role_permissions WHERE permission_code = 'users'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'users_add' FROM role_permissions WHERE permission_code = 'users'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'users_permissions' FROM role_permissions WHERE permission_code = 'users'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'expenses_view' FROM role_permissions WHERE permission_code = 'expenses'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'expenses_add' FROM role_permissions WHERE permission_code = 'expenses'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'suppliers_view' FROM role_permissions WHERE permission_code = 'suppliers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'suppliers_add' FROM role_permissions WHERE permission_code = 'suppliers'",
                "INSERT IGNORE INTO role_permissions (role_id, permission_code) SELECT role_id, 'audit' FROM role_permissions WHERE permission_code = 'backup'",
                "DELETE FROM role_permissions WHERE permission_code IN ('products', 'recipes', 'inventory', 'customers', 'users', 'expenses', 'suppliers')"
            ];
            foreach ($migrations as $sql) {
                $db->exec($sql);
            }
        }
    } catch (Throwable $e) {
        // Safe fallback
    }

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        return;
    }
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        if (str_starts_with(strtoupper($statement), 'CREATE TABLE') || str_starts_with(strtoupper($statement), 'INSERT IGNORE')) {
            $db->exec($statement);
        }
    }
}

function install_database(): void
{
    $server = pdo(false);
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $db = pdo(true);
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('لم يتم العثور على ملف schema.sql');
    }

    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $db->exec($statement);
    }

    ensure_schema_updates();

    $roleId = (int) $db->query("SELECT id FROM roles WHERE code = 'admin'")->fetchColumn();
    $exists = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $exists->execute(['admin']);
    if ((int) $exists->fetchColumn() === 0) {
        $stmt = $db->prepare('INSERT INTO users (name, username, password_hash, role_id, location_id) VALUES (?, ?, ?, ?, NULL)');
        $stmt->execute(['مدير النظام', 'admin', password_hash('admin123', PASSWORD_DEFAULT), $roleId]);
    }
}

function reset_database(): void
{
    $db = pdo(true);
    $db->beginTransaction();
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS=0');
        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $db->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
        }
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    install_database();
}
