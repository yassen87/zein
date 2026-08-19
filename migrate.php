<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) die("❌ Database connection failed.\n");

function safe_alter_col(PDO $pdo, string $table, string $column, string $typeDef): void {
    try {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$typeDef}");
    } catch (Throwable) {
        // Column already exists
    }
}

try {
    // 1. Ensure role/permissions columns exist on admin_users
    safe_alter_col($pdo, 'admin_users', 'role', "VARCHAR(20) NOT NULL DEFAULT 'superadmin'");
    safe_alter_col($pdo, 'admin_users', 'permissions', "TEXT NULL");
    echo "✅ admin_users: role & permissions columns OK\n";

    // 2. Add stock column to product_variants
    safe_alter_col($pdo, 'product_variants', 'stock', "INT NOT NULL DEFAULT 0");
    echo "✅ product_variants: stock column added\n";

    // 3. Add file_sharing_url to products if missing
    safe_alter_col($pdo, 'products', 'file_sharing_url', "TEXT NULL");
    echo "✅ products: file_sharing_url column OK\n";

    // 4. Ensure superadmin role for the first admin
    try {
        $pdo->exec("UPDATE admin_users SET role = 'superadmin' WHERE id = 1");
    } catch (Throwable) {}
    echo "✅ First admin set as superadmin\n";

    // 5. Create homepage_offers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_key VARCHAR(128) NOT NULL,
        link_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");
    echo "✅ homepage_offers: table created OK\n";

    // 5b. Create product_categories pivot table
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        product_id INT(10) UNSIGNED NOT NULL,
        category_slug VARCHAR(100) NOT NULL,
        PRIMARY KEY (product_id, category_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ product_categories: table created OK\n";

    // 5c. Create offer_bundles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS offer_bundles (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name_ar VARCHAR(255) NOT NULL,
        name_en VARCHAR(255) NOT NULL,
        description_ar TEXT,
        description_en TEXT,
        image_key VARCHAR(500) DEFAULT '',
        discount_type ENUM('none','percent','fixed') DEFAULT 'none',
        discount_value DECIMAL(10,2) DEFAULT 0.00,
        active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ offer_bundles: table created OK\n";

    // 6. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(64) NOT NULL UNIQUE,
        setting_value_en TEXT,
        setting_value_ar TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "✅ settings: table created OK\n";

    // Initialize default text settings
    $textSettings = [
        'announce_shipping' => ['Free shipping on orders over 2,000 EGP', 'شحن مجاني للطلبات فوق 2000 ج.م.'],
        'hero_title' => ['Discover Luxury Fragrances', 'اكتشف أفخر العطور'],
        'hero_subtitle' => ['Premium Arabic & French Perfumes', 'عطور عربية وفرنسية فاخرة'],
        'hero_cta_text' => ['Shop Now', 'تسوق الآن'],
        'hero_cta_link' => ['products.php', 'products.php']
    ];

    foreach ($textSettings as $k => [$en, $ar]) {
        $st = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value_en, setting_value_ar) VALUES (?, ?, ?)");
        $st->execute([$k, $en, $ar]);
    }

    // 7. Product reviews table
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
        review_text TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ product_reviews: table created OK\n";

    // 8. Create brands table
    $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name_en VARCHAR(255) NOT NULL,
        name_ar VARCHAR(255) NOT NULL,
        logo VARCHAR(500) NULL,
        description_en TEXT NULL,
        description_ar TEXT NULL,
        is_popular TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ brands: table created OK\n";

    // 9. Add brand_id and is_brand_product columns to products
    safe_alter_col($pdo, 'products', 'brand_id', "INT UNSIGNED NULL");
    safe_alter_col($pdo, 'products', 'is_brand_product', "TINYINT(1) NOT NULL DEFAULT 0");
    echo "✅ products: brand_id & is_brand_product columns OK\n";

    // 10. Add WhatsApp confirmation & payment columns to orders table
    safe_alter_col($pdo, 'orders', 'confirmation_code', "VARCHAR(10) NULL");
    safe_alter_col($pdo, 'orders', 'payment_method', "VARCHAR(32) NOT NULL DEFAULT 'cod'");
    safe_alter_col($pdo, 'orders', 'payment_receipt', "VARCHAR(255) NULL");
    safe_alter_col($pdo, 'orders', 'payment_status', "VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
    safe_alter_col($pdo, 'orders', 'is_confirmed', "TINYINT(1) NOT NULL DEFAULT 0");
    safe_alter_col($pdo, 'orders', 'confirmed_at', "DATETIME NULL");
    safe_alter_col($pdo, 'orders', 'bot_step', "VARCHAR(32) NOT NULL DEFAULT 'initial'");
    safe_alter_col($pdo, 'orders', 'wa_conf_sent', "TINYINT(1) NOT NULL DEFAULT 0");
    safe_alter_col($pdo, 'orders', 'payment_scope', "VARCHAR(32) NOT NULL DEFAULT 'full'");
    safe_alter_col($pdo, 'orders', 'advance_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    safe_alter_col($pdo, 'orders', 'remaining_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    safe_alter_col($pdo, 'orders', 'paid_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    safe_alter_col($pdo, 'orders', 'waived_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    safe_alter_col($pdo, 'orders', 'delivered_at', "DATETIME NULL");
    echo "✅ orders: confirmation & payment columns OK\n";

    // 11. Create receipts directory if not exists
    $receiptsDir = __DIR__ . '/assets/uploads/receipts';
    if (!is_dir($receiptsDir)) {
        @mkdir($receiptsDir, 0777, true);
    }
    echo "✅ receipts directory verified\n";

    // 12. Initialize official InstaPay & Vodafone Cash payment settings
    $settingsDefaults = [
        'instapay_username' => ['ahmedfayoumy1@instapay', 'ahmedfayoumy1@instapay'],
        'instapay_url' => ['https://ipn.eg/S/ahmedfayoumy1/instapay/7H0dWv', 'https://ipn.eg/S/ahmedfayoumy1/instapay/7H0dWv'],
        'vodafone_cash_number' => ['01005250838', '01005250838'],
        'bank_account_info' => ['National Bank of Egypt - Acc: 123456789 - IBAN: EG123456', 'البنك الأهلي المصري - حساب رقم: 123456789 - آيبان: EG123456'],
        'whatsapp_bot_url' => ['http://127.0.0.1:3001', 'http://127.0.0.1:3001'],
        'whatsapp_bot_enabled' => ['1', '1'],
        'whatsapp_bot_phone' => ['201111026600', '201111026600'],
        'women_category_cart_message' => [
            '🌸 Reminder: Perfume is permissible for women at home and commendable to please her husband, but prohibited when going out in public with the intention that non-mahram men smell it.',
            '🌸 تذكرة طيبة: يُباح التعطرُ للنساء داخل المنزل، وهو مُستحبّ إذا كان بهدف إدخال السرور على قلب زوجها، ولكنّه يصبح مُحرماً في حالة التعطر والخروج بقصد أن يشمَّه الرجال الأجانب، وتُؤثم المرأة التي تفعل ذلك، لأنّ في عطرها فتنة للرجال. بنذكر بعض بس 🌸'
        ],
    ];

    foreach ($settingsDefaults as $key => [$valEn, $valAr]) {
        $st = $pdo->prepare("INSERT INTO settings (setting_key, setting_value_en, setting_value_ar) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value_en = VALUES(setting_value_en), setting_value_ar = VALUES(setting_value_ar)");
        $st->execute([$key, $valEn, $valAr]);
    }
    echo "✅ official InstaPay & Vodafone Cash settings updated successfully\n";

    echo "\n🎉 All migrations and database updates completed successfully!\n";

} catch (Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
