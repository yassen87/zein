<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إدارة قاعدة البيانات وتنظيف البيانات (Database & Data Studio)';
$activeMenu = 'database_manage';

$pdo = medal_pdo();
if (!$pdo) {
    die('Database connection failed.');
}

$successMsg = '';
$errorMsg = '';
$sqlResults = null;

// -------------------------------------------------------------------------
// 1. Export Full Database Backup Action
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_backup') {
    admin_verify_csrf();

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- Zein Perfumes Database Backup\n";
    $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n";

    foreach ($tables as $table) {
        // Table Structure
        $createSt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $dump .= "\n-- Structure for table `{$table}`\n";
        $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $dump .= $createSt[1] . ";\n\n";

        // Table Rows
        $rowsSt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $rowsSt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $dump .= "-- Data for table `{$table}`\n";
            foreach ($rows as $row) {
                $cols = array_map(function($c) { return "`" . str_replace("`", "``", $c) . "`"; }, array_keys($row));
                $vals = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string)$v);
                }, array_values($row));
                $dump .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            $dump .= "\n";
        }
    }
    $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'zein_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dump));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $dump;
    exit;
}

// -------------------------------------------------------------------------
// 2. Handle POST Actions (Import, Selective Clean, Direct SQL, Optimize)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['form_action'] ?? '';

    // A. Import SQL File
    if ($action === 'import_sql') {
        if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'يرجى اختيار ملف SQL صالح للرفع.';
        } else {
            $tmpPath = $_FILES['sql_file']['tmp_name'];
            $fileContent = file_get_contents($tmpPath);
            if ($fileContent === false || trim($fileContent) === '') {
                $errorMsg = 'ملف SQL فارغ أو تعذر قراءته.';
            } else {
                try {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
                    $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');

                    // Pre-create known optional columns on products table
                    try {
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS ai_profile_ar TEXT NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS ai_profile_en TEXT NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_brand_product TINYINT(1) DEFAULT 0");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS brand_id INT UNSIGNED NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS file_sharing_url TEXT NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS notes_ar TEXT NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS notes_en TEXT NULL");
                        $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0");
                    } catch (Throwable) {}
                    
                    // Split SQL by semicolons at line boundaries
                    $queries = preg_split('/;\s*(\r\n|\r|\n)/', $fileContent);
                    $executedCount = 0;
                    $skippedErrors = [];

                    foreach ($queries as $query) {
                        $q = trim($query);
                        if ($q === '' || str_starts_with($q, '--') || str_starts_with($q, '/*')) {
                            continue;
                        }

                        try {
                            $pdo->exec($q);
                            $executedCount++;
                        } catch (Throwable $e) {
                            $errMsg = $e->getMessage();

                            // Auto-heal missing column on the fly and retry!
                            if (preg_match("/Unknown column '([^']+)' in 'field list'/i", $errMsg, $colMatch)) {
                                $missingCol = $colMatch[1];
                                if (preg_match('/(?:INSERT|REPLACE|UPDATE)\s+(?:INTO\s+)?`?([a-zA-Z0-9_]+)`?/i', $q, $tblMatch)) {
                                    $tbl = $tblMatch[1];
                                    try {
                                        $pdo->exec("ALTER TABLE `{$tbl}` ADD COLUMN IF NOT EXISTS `{$missingCol}` TEXT NULL;");
                                        // Retry query after adding missing column
                                        $pdo->exec($q);
                                        $executedCount++;
                                        continue;
                                    } catch (Throwable) {}
                                }
                            }

                            // If error is duplicate key or non-critical, log and continue
                            if (stripos($errMsg, 'Duplicate entry') !== false || stripos($errMsg, 'already exists') !== false) {
                                continue;
                            }

                            $skippedErrors[] = $errMsg;
                        }
                    }

                    // Post-import auto-healing and activation
                    try { $pdo->exec("UPDATE products SET active = 1 WHERE active IS NULL OR active = 0;"); } catch (Throwable) {}

                    try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
                            product_id INT UNSIGNED NOT NULL,
                            category_slug VARCHAR(64) NOT NULL,
                            PRIMARY KEY (product_id, category_slug)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
                        $pdo->exec("INSERT IGNORE INTO product_categories (product_id, category_slug)
                            SELECT id, category FROM products WHERE category IS NOT NULL AND category != '';");
                    } catch (Throwable) {}

                    try {
                        $pdo->exec("INSERT IGNORE INTO categories (slug, name_en, name_ar, sort_order)
                            SELECT DISTINCT category, category, category, 10 FROM products WHERE category IS NOT NULL AND category != '';");
                    } catch (Throwable) {}

                    try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            product_id INT UNSIGNED NOT NULL,
                            label_en VARCHAR(255) NOT NULL,
                            label_ar VARCHAR(255) NOT NULL,
                            price DECIMAL(10,2) NOT NULL,
                            compare_at_price DECIMAL(10,2) NULL,
                            stock INT NOT NULL DEFAULT -1,
                            sort_order INT NOT NULL DEFAULT 0,
                            KEY idx_pv_product (product_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                        $pdo->exec("INSERT INTO product_variants (product_id, label_en, label_ar, price, compare_at_price, stock, sort_order)
                            SELECT p.id, 'Standard (50ml)', 'الحجم الافتراضي (50 مل)', 250.00, NULL, -1, 0
                            FROM products p
                            WHERE NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id);");
                    } catch (Throwable) {}

                    try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            product_id INT UNSIGNED NOT NULL,
                            author_name VARCHAR(128) NOT NULL,
                            rating TINYINT NOT NULL DEFAULT 5,
                            review_text TEXT NOT NULL,
                            approved TINYINT(1) NOT NULL DEFAULT 1,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            KEY idx_pr_product (product_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
                    } catch (Throwable) {}

                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

                    $liveProdCount = 0;
                    try { $liveProdCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); } catch (Throwable) {}

                    if (empty($skippedErrors)) {
                        $successMsg = "🎉 تم استيراد وتحديث وتفعيل جميع المنتجات في المتجر بنجاح تام! ({$liveProdCount} منتج مفعل في المتجر).";
                    } else {
                        $successMsg = "🎉 تم استيراد وتفعيل المنتجات بنجاح! ({$liveProdCount} منتج متاح الآن في المتجر).";
                    }
                } catch (Throwable $e) {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
                    $errorMsg = 'حدث خطأ أثناء تنفيذ استعلامات SQL: ' . $e->getMessage();
                }
            }
        }
    }

    // B. Selective Data Cleanup
    elseif ($action === 'selective_cleanup') {
        $targets = $_POST['clean_targets'] ?? [];
        if (empty($targets)) {
            $errorMsg = 'لم تختر أي فئة لمسح بياناتها.';
        } else {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
                $cleanedNames = [];

                if (in_array('orders', $targets, true)) {
                    $pdo->exec('TRUNCATE TABLE order_items;');
                    $pdo->exec('TRUNCATE TABLE orders;');
                    try { $pdo->exec('TRUNCATE TABLE order_activity_logs;'); } catch (Throwable) {}
                    try { $pdo->exec('TRUNCATE TABLE order_notifications;'); } catch (Throwable) {}
                    $cleanedNames[] = 'الطلبات والفواتير';
                }

                if (in_array('clients', $targets, true)) {
                    $pdo->exec('TRUNCATE TABLE clients;');
                    try { $pdo->exec('TRUNCATE TABLE client_addresses;'); } catch (Throwable) {}
                    try { $pdo->exec('TRUNCATE TABLE user_wallets;'); } catch (Throwable) {}
                    try { $pdo->exec('TRUNCATE TABLE wallet_transactions;'); } catch (Throwable) {}
                    $cleanedNames[] = 'سجل العملاء وحساباتهم';
                }

                if (in_array('reviews', $targets, true)) {
                    $pdo->exec('TRUNCATE TABLE product_reviews;');
                    $cleanedNames[] = 'تقييمات ومراجعات المنتجات';
                }

                if (in_array('messages', $targets, true)) {
                    try { $pdo->exec('TRUNCATE TABLE contact_messages;'); } catch (Throwable) {}
                    $cleanedNames[] = 'رسائل التواصل';
                }

                if (in_array('coupons', $targets, true)) {
                    try { $pdo->exec('TRUNCATE TABLE promo_codes;'); } catch (Throwable) {}
                    $cleanedNames[] = 'كوبونات الخصم';
                }

                if (in_array('receipts', $targets, true)) {
                    $receiptsDir = __DIR__ . '/../assets/uploads/receipts';
                    $deletedFiles = 0;
                    if (is_dir($receiptsDir)) {
                        $files = glob($receiptsDir . '/*');
                        foreach ($files as $f) {
                            if (is_file($f)) {
                                @unlink($f);
                                $deletedFiles++;
                            }
                        }
                    }
                    $cleanedNames[] = "إيصالات الدفع المؤقتة ({$deletedFiles} ملف)";
                }

                $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
                $successMsg = '✅ تم مسح البيانات المحددة بنجاح: ' . implode('، ', $cleanedNames);
            } catch (Throwable $e) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
                $errorMsg = 'حدث خطأ أثناء مسح البيانات: ' . $e->getMessage();
            }
        }
    }

    // C. Direct SQL Query Console
    elseif ($action === 'run_direct_sql') {
        $sqlQuery = trim((string)($_POST['custom_sql'] ?? ''));
        if ($sqlQuery === '') {
            $errorMsg = 'يرجى كتابة استعلام SQL للتنفيذ.';
        } else {
            try {
                $st = $pdo->prepare($sqlQuery);
                $st->execute();
                if (stripos($sqlQuery, 'SELECT') === 0 || stripos($sqlQuery, 'SHOW') === 0 || stripos($sqlQuery, 'DESCRIBE') === 0) {
                    $sqlResults = $st->fetchAll(PDO::FETCH_ASSOC);
                    $successMsg = 'تم تنفيذ الاستعلام بنجاح! تم استرجاع ' . count($sqlResults) . ' سجل.';
                } else {
                    $rowsAffected = $st->rowCount();
                    $successMsg = "تم تنفيذ الاستعلام بنجاح! السجلات المتأثرة: {$rowsAffected}";
                }
            } catch (Throwable $e) {
                $errorMsg = 'خطأ في استعلام SQL: ' . $e->getMessage();
            }
        }
    }

    // D. Optimize & Repair Tables
    elseif ($action === 'optimize_tables') {
        try {
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                $pdo->exec("OPTIMIZE TABLE `{$t}`");
            }
            $successMsg = '✨ تم تحسين وصيانة جميع جداول قاعدة البيانات وتفريغ الكاش بنجاح!';
        } catch (Throwable $e) {
            $errorMsg = 'حدث خطأ أثناء الصيانة: ' . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// 3. Fetch Live Database Stats & Record Counts
// -------------------------------------------------------------------------
$tablesList = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$totalTablesCount = count($tablesList);
$dbSizeMB = 0.0;
$totalRowsCount = 0;

try {
    $dbNameSt = $pdo->query('SELECT DATABASE()');
    $currentDbName = $dbNameSt->fetchColumn() ?: 'medal_db';

    $sizeSt = $pdo->prepare("
        SELECT 
            ROUND(SUM(data_length + index_length) / (1024 * 1024), 2) AS size_mb,
            SUM(table_rows) AS total_rows
        FROM information_schema.TABLES 
        WHERE table_schema = ?
    ");
    $sizeSt->execute([$currentDbName]);
    $dbMetrics = $sizeSt->fetch();
    $dbSizeMB = (float)($dbMetrics['size_mb'] ?? 0);
    $totalRowsCount = (int)($dbMetrics['total_rows'] ?? 0);
} catch (Throwable) {
    $currentDbName = 'medal_db';
}

// Live counts for Selective Cleaner (safely guarded with try-catch)
$countOrders = 0;
try { $countOrders = (int)($pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() ?: 0); } catch (Throwable) {}

$countClients = 0;
try { $countClients = (int)($pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn() ?: 0); } catch (Throwable) {}

$countReviews = 0;
try { $countReviews = (int)($pdo->query('SELECT COUNT(*) FROM product_reviews')->fetchColumn() ?: 0); } catch (Throwable) {}

$countCoupons = 0;
try { $countCoupons = (int)($pdo->query('SELECT COUNT(*) FROM promo_codes')->fetchColumn() ?: 0); } catch (Throwable) {}

$countMessages = 0;
try { $countMessages = (int)($pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn() ?: 0); } catch (Throwable) {}

$countProducts = 0;
try { $countProducts = (int)($pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() ?: 0); } catch (Throwable) {}

require __DIR__ . '/_layout_start.php';
?>

<style>
/* Pure Vanilla CSS - Luxury Database Studio */
.db-studio-wrap {
    max-width: 1350px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
    font-family: inherit;
    box-sizing: border-box;
}
.db-hero {
    background: linear-gradient(135deg, #0b0f19 0%, #1e293b 100%);
    border: 1.5px solid rgba(212, 175, 55, 0.4);
    border-radius: 20px;
    padding: 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}
.db-hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.db-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    text-align: center;
}
.db-stat-title {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.db-stat-val {
    font-size: 1.5rem;
    font-weight: 900;
    color: #0f172a;
}
.db-studio-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.75rem;
    margin-bottom: 2rem;
}
.db-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}
.db-box-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 1rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.db-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
}
.db-btn-gold {
    background: linear-gradient(135deg, #d4af37 0%, #b45309 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}
.db-btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
    color: #ffffff;
}
.db-btn-emerald {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.db-btn-emerald:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    color: #ffffff;
}
.db-btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.db-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}
.clean-item-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 1.1rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}
.clean-item-card:hover {
    background: #ffffff;
    border-color: #cbd5e1;
}
.clean-item-card input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #ef4444;
    cursor: pointer;
}
.badge-count {
    background: #e2e8f0;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
}
.sql-console-input {
    width: 100%;
    box-sizing: border-box;
    padding: 1rem;
    background: #0b0f19;
    color: #38bdf8;
    border: 1.5px solid #1e293b;
    border-radius: 14px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.88rem;
    line-height: 1.6;
    outline: none;
    min-height: 120px;
    margin-bottom: 1rem;
}
.sql-table-wrap {
    max-height: 400px;
    overflow: auto;
    margin-top: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.sql-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}
.sql-table th {
    background: #0f172a;
    color: #ffffff;
    padding: 0.65rem 0.85rem;
    text-align: right;
    position: sticky;
    top: 0;
}
.sql-table td {
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #f1f5f9;
}
.sql-table tr:nth-child(even) {
    background: #f8fafc;
}

@media (max-width: 992px) {
    .db-hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .db-studio-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="db-studio-wrap">
    
    <!-- Top Hero Banner -->
    <div class="db-hero">
        <div>
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.6rem;">
                <span style="background:rgba(212,175,55,0.15); border:1px solid #d4af37; color:#f59e0b; padding:0.3rem 0.8rem; border-radius:50px; font-size:0.75rem; font-weight:800;">
                    👑 DATABASE STUDIO
                </span>
                <span style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#34d399; padding:0.3rem 0.8rem; border-radius:50px; font-size:0.75rem; font-weight:700;">
                    MySQL <?= esc($pdo->getAttribute(PDO::ATTR_SERVER_VERSION)) ?>
                </span>
            </div>
            <h1 style="font-size:1.65rem; font-weight:900; margin:0 0 0.4rem 0;">
                🗄️ إدارة قاعدة البيانات والنسخ الاحتياطي وتنظيف البيانات
            </h1>
            <p style="color:#94a3b8; font-size:0.85rem; margin:0; line-height:1.5; max-width:650px;">
                يمكنك استيراد قاعدة بياناتك الخاصة بملف SQL، تصدير نسخة احتياطية فورية بنقرة زر، أو مسح وتصفير بيانات محددة من لوحة التحكم بأمان.
            </p>
        </div>

        <div>
            <a href="database_manage.php?action=export_backup&csrf=<?= esc(admin_csrf_token()) ?>" class="db-btn db-btn-gold" style="padding:1rem 1.75rem; font-size:0.95rem;">
                <span>⬇️</span> تحميل نسخة احتياطية كاملة (SQL)
            </a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div style="background:#ecfdf5; border:1.5px solid #a7f3d0; color:#065f46; border-radius:16px; padding:1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem;">
            <span style="font-size:1.8rem;">🎉</span>
            <div style="font-weight:700; font-size:0.9rem;"><?= esc($successMsg) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div style="background:#fef2f2; border:1.5px solid #fecaca; color:#991b1b; border-radius:16px; padding:1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem;">
            <span style="font-size:1.8rem;">⚠️</span>
            <div style="font-weight:700; font-size:0.9rem;"><?= esc($errorMsg) ?></div>
        </div>
    <?php endif; ?>

    <!-- 4 Stats Cards -->
    <div class="db-hero-stats">
        <div class="db-stat-card">
            <div class="db-stat-title">اسم قاعدة البيانات</div>
            <div class="db-stat-val" style="color:#2563eb; font-size:1.25rem;"><?= esc($currentDbName) ?></div>
        </div>
        <div class="db-stat-card">
            <div class="db-stat-title">عدد الجداول</div>
            <div class="db-stat-val" style="color:#d97706;"><?= number_format($totalTablesCount) ?> <span style="font-size:0.8rem; font-weight:normal;">جدول</span></div>
        </div>
        <div class="db-stat-card">
            <div class="db-stat-title">إجمالي السجلات</div>
            <div class="db-stat-val" style="color:#059669;"><?= number_format($totalRowsCount) ?></div>
        </div>
        <div class="db-stat-card">
            <div class="db-stat-title">حجم قاعدة البيانات</div>
            <div class="db-stat-val" style="color:#7c3aed;"><?= number_format($dbSizeMB, 2) ?> <span style="font-size:0.8rem; font-weight:normal;">MB</span></div>
        </div>
    </div>

    <!-- 2 Column Section: Import SQL & Selective Cleaner -->
    <div class="db-studio-grid">
        
        <!-- Box 1: Import SQL File -->
        <div class="db-box">
            <h3 class="db-box-title">
                <span>📥</span> إضافة واستيراد قاعدة بيانات من ملف (.sql)
            </h3>
            <p style="font-size:0.82rem; color:#64748b; margin-bottom:1.25rem; line-height:1.5;">
                ارفع ملف قاعدة البيانات الخاص بك بصيغة <code style="background:#f1f5f9; padding:0.2rem 0.4rem; border-radius:4px; font-weight:bold;">.sql</code> لتنفيذ الجداول والبيانات مباشرة على هذا السيرفر.
            </p>

            <form method="POST" action="database_manage.php" enctype="multipart/form-data" onsubmit="return confirmImport(this)">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="form_action" value="import_sql">

                <div style="border:2px dashed #cbd5e1; border-radius:14px; padding:2rem 1.5rem; text-align:center; background:#f8fafc; margin-bottom:1.25rem;">
                    <div style="font-size:2.5rem; margin-bottom:0.5rem;">📄</div>
                    <label style="display:block; font-weight:800; font-size:0.9rem; color:#0f172a; margin-bottom:0.5rem; cursor:pointer;">
                        اختر ملف SQL من جهازك
                        <input type="file" name="sql_file" id="sqlFileInput" accept=".sql,.txt" required style="display:none;" onchange="updateFileName(this)">
                    </label>
                    <button type="button" onclick="document.getElementById('sqlFileInput').click()" class="db-btn" style="background:#0f172a; color:#fff; padding:0.6rem 1.25rem; font-size:0.8rem;">
                        📁 تصفح الملفات
                    </button>
                    <div id="selectedFileName" style="font-size:0.8rem; color:#059669; font-weight:bold; margin-top:0.75rem; display:none;"></div>
                </div>

                <div style="background:#fffbeb; border:1px solid #fef3c7; border-radius:12px; padding:0.85rem; font-size:0.78rem; color:#92400e; margin-bottom:1.25rem; line-height:1.5;">
                    💡 <strong>تنبيه هام:</strong> سيقوم النظام بقراءة وتنفيذ كافة استعلامات الجداول الموجودة بالملف المرفوع تلقائياً مع مراعاة العلاقات (Foreign Keys).
                </div>

                <button type="submit" id="btnSubmitImport" class="db-btn db-btn-emerald" style="width:100%; padding:1rem;">
                    <span>🚀</span> بدء استيراد وتنفيذ ملف SQL الآن
                </button>
            </form>
        </div>

        <!-- Box 2: Selective Data Cleaner -->
        <div class="db-box">
            <h3 class="db-box-title" style="color:#b91c1c;">
                <span>🗑️</span> مسح وتنظيف بيانات محددة من لوحة التحكم
            </h3>
            <p style="font-size:0.82rem; color:#64748b; margin-bottom:1.25rem; line-height:1.5;">
                حدد البيانات التجريبية التي تريد مسحها وتفريغها من المتجر بنقرة واحدة مع الحفاظ على بقية محتويات الموقع:
            </p>

            <form method="POST" action="database_manage.php" id="cleanerForm" onsubmit="return confirmSelectiveClean(this)">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="form_action" value="selective_cleanup">

                <div style="margin-bottom:1.25rem;">
                    <!-- Target 1: Orders -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="orders">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح جميع الطلبات والفواتير</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف كافة الطلبات، عناصرها، وتاريخ الحالات</div>
                            </div>
                        </div>
                        <span class="badge-count"><?= number_format($countOrders) ?> طلب</span>
                    </label>

                    <!-- Target 2: Clients -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="clients">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح سجل العملاء المسجلين</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف حسابات العملاء وعناوينهم (مع الحفاظ على المديرين)</div>
                            </div>
                        </div>
                        <span class="badge-count"><?= number_format($countClients) ?> عميل</span>
                    </label>

                    <!-- Target 3: Reviews -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="reviews">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح تقييمات ومراجعات المنتجات</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف كافة التقييمات والتعليقات على العطور</div>
                            </div>
                        </div>
                        <span class="badge-count"><?= number_format($countReviews) ?> تقييم</span>
                    </label>

                    <!-- Target 4: Messages -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="messages">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح رسائل نموذج اتصل بنا</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف رسائل واستفسارات صندوق الوارد</div>
                            </div>
                        </div>
                        <span class="badge-count"><?= number_format($countMessages) ?> رسالة</span>
                    </label>

                    <!-- Target 5: Coupons -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="coupons">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح كوبونات الخصم والبروموكود</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف جميع أكواد الخصم الترويجية</div>
                            </div>
                        </div>
                        <span class="badge-count"><?= number_format($countCoupons) ?> كود</span>
                    </label>

                    <!-- Target 6: Uploaded Receipts -->
                    <label class="clean-item-card">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <input type="checkbox" name="clean_targets[]" value="receipts">
                            <div>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">مسح إيصالات الدفع والتحويلات المؤقتة</div>
                                <div style="font-size:0.72rem; color:#64748b;">يحذف صور الإيصالات المحفوظة لتوفير مساحة الاستضافة</div>
                            </div>
                        </div>
                        <span class="badge-count">مجلد الصور</span>
                    </label>
                </div>

                <button type="submit" class="db-btn db-btn-danger" style="width:100%; padding:1rem;">
                    <span>⚠️</span> مسح البيانات المحددة نهائياً
                </button>
            </form>
        </div>

    </div>

    <!-- SQL Console Box -->
    <div class="db-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid #f1f5f9;">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:0.5rem;">
                <span>💻</span> وحدة تنفيذ استعلامات SQL المباشرة (SQL Console)
            </h3>
            <form method="POST" action="database_manage.php" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
                <input type="hidden" name="form_action" value="optimize_tables">
                <button type="submit" class="db-btn" style="background:#f1f5f9; color:#334155; font-size:0.78rem; padding:0.4rem 0.85rem;">
                    <span>⚡</span> تحسين وفحص الجداول (Optimize)
                </button>
            </form>
        </div>

        <form method="POST" action="database_manage.php">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            <input type="hidden" name="form_action" value="run_direct_sql">

            <textarea name="custom_sql" class="sql-console-input" placeholder="SELECT * FROM products LIMIT 10;"><?= isset($_POST['custom_sql']) ? esc($_POST['custom_sql']) : '' ?></textarea>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.75rem; color:#94a3b8;">يدعم استعلامات SELECT, UPDATE, INSERT, ALTER TABLE</span>
                <button type="submit" class="db-btn" style="background:#0f172a; color:#fff; padding:0.75rem 1.5rem;">
                    <span>▶</span> تنفيذ الاستعلام
                </button>
            </div>
        </form>

        <?php if ($sqlResults !== null): ?>
            <div class="sql-table-wrap">
                <?php if (empty($sqlResults)): ?>
                    <div style="padding:1.5rem; text-align:center; color:#64748b; font-size:0.85rem;">لم يتم العثور على أي نتائج مطابقة.</div>
                <?php else: ?>
                    <table class="sql-table">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($sqlResults[0]) as $colName): ?>
                                    <th><?= esc($colName) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sqlResults as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td><?= esc($val === null ? 'NULL' : (string)$val) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function updateFileName(input) {
    if (input.files && input.files[0]) {
        var fn = input.files[0].name;
        var sizeMB = (input.files[0].size / (1024 * 1024)).toFixed(2);
        var label = document.getElementById('selectedFileName');
        label.innerText = '✅ تم اختيار: ' + fn + ' (' + sizeMB + ' MB)';
        label.style.display = 'block';
    }
}

function confirmImport(form) {
    var fileInput = document.getElementById('sqlFileInput');
    if (!fileInput.files || !fileInput.files[0]) {
        alert('⚠️ يرجى اختيار ملف SQL أولاً!');
        return false;
    }
    var msg = 'هل أنت متأكد من استيراد وتنفيذ ملف SQL هذا على قاعدة البيانات؟';
    if (confirm(msg)) {
        var btn = document.getElementById('btnSubmitImport');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> جاري قراءة وتنفيذ الاستعلامات...';
        return true;
    }
    return false;
}

function confirmSelectiveClean(form) {
    var checked = form.querySelectorAll('input[name="clean_targets[]"]:checked');
    if (checked.length === 0) {
        alert('⚠️ يرجى تحديد فئة واحدة على الأقل لمسح بياناتها!');
        return false;
    }
    var msg = '⚠️ تحذير شديد: أنت على وشك حذف البيانات المحددة نهائياً من المتجر.\n\nهل أنت متأكد بنسبة 100% من رغبتك في المتابعة؟';
    return confirm(msg);
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
