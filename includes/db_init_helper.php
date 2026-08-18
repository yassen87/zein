<?php
declare(strict_types=1);

/**
 * Database Initialization Helper
 * Handles schema import, migrations, and config updates.
 */

/**
 * Runs the SQL schema from database/schema.sql
 */
function medal_db_run_schema(PDO $pdo): bool
{
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        return false;
    }

    $schema = file_get_contents($schemaFile);
    
    // Simple split by semicolon. For more complex schemas, a better parser would be needed.
    // However, the current schema is simple.
    $queries = preg_split('/;\s*(\n|$)/', $schema);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '' || strpos($query, '--') === 0 || strpos($query, '/*') === 0) {
            continue;
        }
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            // Ignore errors if table already exists or similar, 
            // but in a clean setup this should work.
        }
    }
    return true;
}

/**
 * Runs initial migrations (admin user, categories, etc.)
 */
function medal_db_run_migrations(PDO $pdo, string $adminUser, string $adminPass): bool
{
    try {
        // 1. Create Admin User
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
        $st->execute([$adminUser, $hash]);

        // 2. Create Default Categories
        $cats = [
            ['women', 'Women', 'نساء', 10],
            ['men', 'Men', 'رجال', 20],
            ['unisex', 'Unisex', 'للجنسين', 30],
        ];
        $ic = $pdo->prepare('INSERT INTO categories (slug, name_en, name_ar, sort_order) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE name_en=VALUES(name_en), name_ar=VALUES(name_ar), sort_order=VALUES(sort_order)');
        foreach ($cats as $c) {
            $ic->execute($c);
        }

        // 3. Add mandatory fields if missing (from fix_hostinger_database.php)
        @$pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL AFTER description_ar");
        @$pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS email_conf_sent TINYINT(1) DEFAULT 0");

        // 4. Create internal products tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS internal_products (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name_en VARCHAR(255) NOT NULL,
                name_ar VARCHAR(255) NOT NULL,
                description TEXT,
                cost DECIMAL(10, 2) DEFAULT 0.00,
                type ENUM('gift', 'sample', 'promotional') DEFAULT 'gift',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS order_internal_products (
                id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT(10) UNSIGNED NOT NULL,
                internal_product_id INT(10) UNSIGNED NOT NULL,
                quantity INT DEFAULT 1,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_oip_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_oip_product FOREIGN KEY (internal_product_id) REFERENCES internal_products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Updates or creates the db.local.php file
 */
function medal_db_update_config(string $host, string $dbname, string $user, string $pass): bool
{
    $configFile = dirname(__DIR__) . '/includes/db.local.php';
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $content = "<?php\n";
    $content .= "declare(strict_types=1);\n\n";
    $content .= "define('MEDAL_DB_DSN', " . var_export($dsn, true) . ");\n";
    $content .= "define('MEDAL_DB_USER', " . var_export($user, true) . ");\n";
    $content .= "define('MEDAL_DB_PASS', " . var_export($pass, true) . ");\n";

    return file_put_contents($configFile, $content) !== false;
}
