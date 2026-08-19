<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pdo = medal_pdo();
if (!$pdo) die("❌ Database connection failed.");

try {
    // 1. Ensure role/permissions columns exist on admin_users
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'superadmin'");
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS permissions TEXT NULL");
    echo "✅ admin_users: role & permissions columns OK<br>";

    // 2. Add stock column to product_variants
    $pdo->exec("ALTER TABLE product_variants ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 0");
    echo "✅ product_variants: stock column added<br>";

    // 3. Add file_sharing_url to products if missing
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL");
    echo "✅ products: file_sharing_url column OK<br>";

    // 4. Ensure superadmin role for the first admin
    $pdo->exec("UPDATE admin_users SET role = 'superadmin' WHERE id = 1");
    echo "✅ First admin set as superadmin<br>";

    // 5. Create homepage_offers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_offers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image_key VARCHAR(128) NOT NULL,
        link_url VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");
    echo "✅ homepage_offers: table created OK<br>";

    // 5b. Create product_categories pivot table
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        product_id INT(10) UNSIGNED NOT NULL,
        category_slug VARCHAR(100) NOT NULL,
        PRIMARY KEY (product_id, category_slug),
        CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ product_categories: table created OK<br>";

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
    echo "✅ offer_bundles: table created OK<br>";

    // 6. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(64) NOT NULL UNIQUE,
        setting_value_en TEXT,
        setting_value_ar TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "✅ settings: table created OK<br>";

    // Initialize announce_shipping if not present
    $st = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value_en, setting_value_ar) VALUES (?, ?, ?)");
    $st->execute([
        'announce_shipping',
        'Free shipping on orders over 2,000 EGP',
        'شحن مجاني للطلبات فوق 2000 ج.م.'
    ]);
    echo "✅ settings: announce_shipping initialized<br>";

    // Initialize hero_title if not present
    $st->execute([
        'hero_title',
        'Discover Luxury Fragrances',
        'اكتشف أفخر العطور'
    ]);
    echo "✅ settings: hero_title initialized<br>";

    // Initialize hero_subtitle if not present
    $st->execute([
        'hero_subtitle',
        'Premium Arabic & French Perfumes',
        'عطور عربية وفرنسية فاخرة'
    ]);
    echo "✅ settings: hero_subtitle initialized<br>";

    // Initialize hero_cta_text if not present
    $st->execute([
        'hero_cta_text',
        'Shop Now',
        'تسوق الآن'
    ]);
    echo "✅ settings: hero_cta_text initialized<br>";

    // Initialize hero_cta_link if not present
    $st->execute([
        'hero_cta_link',
        'products.php',
        'products.php'
    ]);
    echo "✅ settings: hero_cta_link initialized<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
        review_text TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_pr_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ product_reviews: table created OK<br>";

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
    echo "✅ brands: table created OK<br>";

    // 9. Add brand_id and is_brand_product columns to products if missing
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS brand_id INT UNSIGNED NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_brand_product TINYINT(1) NOT NULL DEFAULT 0");
    echo "✅ products: brand_id & is_brand_product columns OK<br>";

    // 10. Add WhatsApp confirmation & payment columns to orders table
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmation_code VARCHAR(10) NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(32) NOT NULL DEFAULT 'cod'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_receipt VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS is_confirmed TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmed_at DATETIME NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS bot_step VARCHAR(32) NOT NULL DEFAULT 'initial'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS wa_conf_sent TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS waived_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL");
    echo "✅ orders: confirmation & payment columns OK<br>";

    // 11. Create receipts directory if not exists
    $receiptsDir = __DIR__ . '/assets/uploads/receipts';
    if (!is_dir($receiptsDir)) {
        @mkdir($receiptsDir, 0777, true);
    }
    echo "✅ receipts directory verified<br>";

    // 12. Initialize payment and bot settings
    $settingsDefaults = [
        'instapay_username' => ['ahmedfayoumy1@instapay', 'ahmedfayoumy1@instapay'],
        'instapay_url' => ['https://ipn.eg/S/ahmedfayoumy1/instapay/7H0dWv', 'https://ipn.eg/S/ahmedfayoumy1/instapay/7H0dWv'],
        'vodafone_cash_number' => ['01005250838', '01005250838'],
        'bank_account_info' => ['National Bank of Egypt - Acc: 123456789 - IBAN: EG123456', 'البنك الأهلي المصري - حساب رقم: 123456789 - آيبان: EG123456'],
        'whatsapp_bot_url' => ['http://127.0.0.1:3001', 'http://127.0.0.1:3001'],
        'whatsapp_bot_enabled' => ['1', '1'],
        'whatsapp_bot_phone' => ['201111026600', '201111026600'],
    ];

    foreach ($settingsDefaults as $key => [$valEn, $valAr]) {
        $st = $pdo->prepare("INSERT INTO settings (setting_key, setting_value_en, setting_value_ar) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value_en = VALUES(setting_value_en), setting_value_ar = VALUES(setting_value_ar)");
        $st->execute([$key, $valEn, $valAr]);
    }
    echo "✅ payment & bot settings initialized<br>";

    echo "<br><strong style='color:green'>✅ All migrations completed successfully!</strong>";
    echo "<br><a href='admin/index.php'>Go to Admin Panel →</a>";

} catch (Throwable $e) {
    echo "❌ Migration failed: " . htmlspecialchars($e->getMessage());
}
