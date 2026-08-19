<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/admin_bootstrap.php';
require_once __DIR__ . '/includes/maintenance_helper.php';

$pdo = medal_pdo();
if (!$pdo) {
    die('Database connection failed. Please check MySQL settings.');
}

$loginError = '';
$successMsg = '';
$errorMsg = '';
$sqlResults = null;

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
    header('Location: developer.php');
    exit;
}

// -------------------------------------------------------------------------
// Authentication Handler
// -------------------------------------------------------------------------
if (!admin_is_logged_in()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dev_login'])) {
        admin_verify_csrf();
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $loginError = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
        } else {
            $st = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
            $st->execute([$username]);
            $userRow = $st->fetch();

            if ($userRow && password_verify($password, (string)$userRow['password_hash'])) {
                admin_login((int)$userRow['id']);
                header('Location: developer.php');
                exit;
            } else {
                $loginError = 'بيانات الدخول غير صحيحة. يرجى التأكد من الحساب.';
            }
        }
    }

    // Render Standalone Developer Login Screen
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>تسجيل دخول بوابة المطورين — زين للعطور</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: #090d16;
                color: #f8fafc;
                font-family: 'Tajawal', sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                position: relative;
                overflow: hidden;
            }
            .bg-glow {
                position: absolute;
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, rgba(9, 13, 22, 0) 70%);
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
            }
            .login-card {
                background: rgba(15, 23, 42, 0.85);
                border: 1px solid rgba(212, 175, 55, 0.35);
                backdrop-filter: blur(16px);
                border-radius: 24px;
                padding: 3rem 2.5rem;
                max-width: 440px;
                width: 100%;
                position: relative;
                z-index: 1;
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 35px rgba(212, 175, 55, 0.1);
            }
            .icon-wrap {
                width: 72px;
                height: 72px;
                background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(99, 102, 241, 0.1) 100%);
                border: 2px solid #d4af37;
                border-radius: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 2.2rem;
                box-shadow: 0 0 25px rgba(212, 175, 55, 0.3);
            }
            h1 {
                font-size: 1.6rem;
                font-weight: 900;
                text-align: center;
                margin-bottom: 0.4rem;
                background: linear-gradient(135deg, #ffffff 40%, #d4af37 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            p.sub {
                text-align: center;
                color: #94a3b8;
                font-size: 0.88rem;
                margin-bottom: 2rem;
            }
            .form-group {
                margin-bottom: 1.25rem;
            }
            label {
                display: block;
                font-size: 0.85rem;
                font-weight: 700;
                color: #cbd5e1;
                margin-bottom: 0.5rem;
            }
            input {
                width: 100%;
                background: #090d16;
                border: 1px solid #334155;
                color: #ffffff;
                padding: 0.85rem 1.2rem;
                border-radius: 12px;
                font-size: 0.95rem;
                font-family: inherit;
                outline: none;
                transition: border-color 0.2s;
            }
            input:focus {
                border-color: #d4af37;
                box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            }
            .btn-submit {
                width: 100%;
                background: linear-gradient(135deg, #d4af37 0%, #aa8420 100%);
                color: #090d16;
                font-weight: 800;
                font-size: 1.05rem;
                padding: 0.9rem;
                border-radius: 12px;
                border: none;
                cursor: pointer;
                box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
                transition: all 0.2s;
                margin-top: 0.5rem;
            }
            .btn-submit:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(212, 175, 55, 0.5);
            }
            .alert-error {
                background: rgba(239, 68, 68, 0.15);
                border: 1px solid rgba(239, 68, 68, 0.4);
                color: #fca5a5;
                padding: 0.75rem 1rem;
                border-radius: 10px;
                font-size: 0.88rem;
                margin-bottom: 1.25rem;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="bg-glow"></div>
        <div class="login-card">
            <div class="icon-wrap">⚡</div>
            <h1>بوابة تحكم المطورين</h1>
            <p class="sub">سجل الدخول بحساب الأدمن للوصول لأدوات النظام وقفل/فتح المتجر</p>

            <?php if ($loginError): ?>
                <div class="alert-error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>

            <form method="post" action="developer.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                <input type="hidden" name="dev_login" value="1">

                <div class="form-group">
                    <label>اسم المستخدم (Admin Username):</label>
                    <input type="text" name="username" required autofocus placeholder="مثال: admin / zein">
                </div>

                <div class="form-group">
                    <label>كلمة المرور (Password):</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-submit">⚡ تسجيل الدخول والتحكم</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// -------------------------------------------------------------------------
// 1. Export Full Database Backup Action (.sql)
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_backup') {
    admin_verify_csrf();

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- =========================================================\n";
    $dump .= "-- Zein Perfumes Official Full Database Backup\n";
    $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    $dump .= "-- =========================================================\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n";

    foreach ($tables as $table) {
        $createSt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $dump .= "\n-- --------------------------------------------------------\n";
        $dump .= "-- Structure for table `{$table}`\n";
        $dump .= "-- --------------------------------------------------------\n";
        $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $dump .= $createSt[1] . ";\n\n";

        $rowsSt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $rowsSt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $dump .= "-- Data for table `{$table}` (" . count($rows) . " rows)\n";
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

    $filename = 'zein_perfumes_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dump));
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $dump;
    exit;
}

// -------------------------------------------------------------------------
// Helper: Import SQL Content
// -------------------------------------------------------------------------
function run_dev_sql_import(PDO $pdo, string $fileContent): array {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');

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
            $msg = $e->getMessage();
            if (!str_contains($msg, 'already exists') && !str_contains($msg, 'Duplicate column')) {
                $skippedErrors[] = substr($q, 0, 80) . '... => ' . $msg;
            }
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    return ['executed' => $executedCount, 'errors' => $skippedErrors];
}

// -------------------------------------------------------------------------
// 2. Handle POST Actions (Toggle Maintenance, Restore, Import, Clear Cache)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['form_action'] ?? '';

    // Action: 1-Click Toggle Maintenance (Open/Close Site)
    if ($action === 'toggle_maintenance') {
        $currentState = is_maintenance_enabled();
        $newState = !$currentState;
        $saved = save_maintenance_config([
            'enabled' => $newState,
            'title_ar' => 'الموقع قيد الصيانة والتحديث الفاخر',
            'title_en' => 'Store Under Luxury Maintenance',
            'message_ar' => 'نقوم حالياً بتحديث متجر زين للعطور وإضافة تشكيلات جديدة حصرية. سنعود للعمل بكامل طاقتنا قريباً جداً!',
            'message_en' => 'We are currently updating Zain Perfumes store with exclusive collections. We will be back shortly!'
        ]);

        if ($saved) {
            $successMsg = $newState ? '🔒 تم قفل الموقع وتفعيل وضع الصيانة بنجاح!' : '🔓 تم فتح الموقع للزوار بنجاح (المتجر Online الآن)!';
        } else {
            $errorMsg = 'تعذر تغيير حالة قفل الموقع.';
        }
    }

    // Action: 1-Click Restore Official Database Dump (140+ Perfumes)
    elseif ($action === 'restore_official_dump') {
        $dumpPath = __DIR__ . '/includes/u868008675_zein.sql';
        if (!file_exists($dumpPath)) {
            $errorMsg = 'ملف قاعدة البيانات الرسمي u868008675_zein.sql غير موجود في مجلد includes!';
        } else {
            $sqlContent = file_get_contents($dumpPath);
            if (!$sqlContent) {
                $errorMsg = 'فشل قراءة ملف النسخة الاحتياطية الرسمية!';
            } else {
                $res = run_dev_sql_import($pdo, $sqlContent);
                medal_ensure_orders_schema($pdo);
                $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $successMsg = "🎉 تم استرجاع وتفعيل قاعدة البيانات الرسمية بنجاح! إجمالي العطور المفعلة: {$prodCount} عطر (تم تنفيذ {$res['executed']} استعلام).";
            }
        }
    }

    // Action: Upload & Import Custom SQL File
    elseif ($action === 'import_sql_file') {
        if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'يرجى اختيار ملف SQL صالح للرفع.';
        } else {
            $fileTmp = $_FILES['sql_file']['tmp_name'];
            $fileContent = file_get_contents($fileTmp);
            if (!$fileContent) {
                $errorMsg = 'الملف المرفوع فارغ أو تعذر قراءته.';
            } else {
                $res = run_dev_sql_import($pdo, $fileContent);
                medal_ensure_orders_schema($pdo);
                $successMsg = "✅ تم استيراد ملف الـ SQL بنجاح! (تم تنفيذ {$res['executed']} استعلام).";
            }
        }
    }

    // Action: Clear OPcache & System Cache
    elseif ($action === 'clear_cache') {
        $cleared = [];
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $cleared[] = 'PHP OPcache';
        }
        @touch(__DIR__ . '/includes/config.php');
        $cleared[] = 'Session & Config Cache';
        $successMsg = '⚡ تم مسح وإعادة تعيين الكاش بنجاح: ' . implode(' + ', $cleared);
    }

    // Action: Execute SQL Query
    elseif ($action === 'run_sql_query') {
        $query = trim($_POST['raw_sql'] ?? '');
        if ($query === '') {
            $errorMsg = 'يرجى كتابة استعلام SQL للتنفيذ.';
        } else {
            try {
                $st = $pdo->query($query);
                if ($st) {
                    $sqlResults = $st->fetchAll(PDO::FETCH_ASSOC);
                    $successMsg = '✓ تم تنفيذ الاستعلام بنجاح! عدد الصفوف: ' . count($sqlResults);
                } else {
                    $successMsg = '✓ تم تنفيذ الاستعلام بنجاح.';
                }
            } catch (Throwable $e) {
                $errorMsg = 'خطأ SQL: ' . $e->getMessage();
            }
        }
    }
}

$maintCfg = get_maintenance_config();
$isMaintActive = !empty($maintCfg['enabled']);

// Fetch Database Diagnostics
$dbTables = [];
try {
    $tbls = $pdo->query('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tbls as $t) {
        $dbTables[] = [
            'name' => $t['Name'] ?? '',
            'rows' => (int)($t['Rows'] ?? 0),
            'size' => round(((float)($t['Data_length'] ?? 0) + (float)($t['Index_length'] ?? 0)) / 1024, 2) . ' KB',
        ];
    }
} catch (Throwable) {}

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalClients = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// Check WhatsApp Bot API Status
$waBotOnline = false;
try {
    $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
    $resp = @file_get_contents('http://127.0.0.1:3001/api/status', false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['success']) && $data['status'] === 'ready') {
            $waBotOnline = true;
        }
    }
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ بوابة تحكم المطورين والسوبر أدمن — زين للعطور</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #090d16;
            color: #f8fafc;
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .dev-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 1.5rem;
            border-radius: 18px;
            backdrop-filter: blur(10px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 900;
            color: #fff;
        }
        .brand span.icon {
            font-size: 1.6rem;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-link:hover {
            color: #d4af37;
            background: rgba(212, 175, 55, 0.1);
        }
        .nav-link-logout {
            color: #ef4444;
        }
        .nav-link-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* ── BIG INSTANT LOCK / UNLOCK HERO BUTTON ── */
        .lock-hero {
            background: <?= $isMaintActive ? 'linear-gradient(135deg, #450a0a 0%, #7f1d1d 100%)' : 'linear-gradient(135deg, #064e3b 0%, #065f46 100%)' ?>;
            border: 2px solid <?= $isMaintActive ? '#ef4444' : '#10b981' ?>;
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 15px 40px -10px <?= $isMaintActive ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)' ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .lock-info h2 {
            font-size: 1.85rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lock-info p {
            color: #e2e8f0;
            font-size: 1rem;
            line-height: 1.6;
            max-width: 650px;
        }
        .btn-toggle-lock {
            background: <?= $isMaintActive ? '#10b981' : '#ef4444' ?>;
            color: #ffffff;
            font-weight: 900;
            font-size: 1.15rem;
            padding: 1.15rem 2.25rem;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 25px <?= $isMaintActive ? 'rgba(16, 185, 129, 0.5)' : 'rgba(239, 68, 68, 0.5)' ?>;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        .btn-toggle-lock:hover {
            transform: scale(1.04);
            filter: brightness(1.1);
        }

        /* ── GRID MODULES ── */
        .dev-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2.5rem;
        }
        .dev-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.2s;
        }
        .dev-card:hover {
            border-color: rgba(212, 175, 55, 0.4);
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.08);
        }
        .dev-card-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.75rem;
        }
        .dev-card-desc {
            color: #94a3b8;
            font-size: 0.92rem;
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }
        .btn-gold {
            background: linear-gradient(135deg, #d4af37 0%, #aa8420 100%);
            color: #090d16 !important;
            font-weight: 800;
            padding: 0.9rem 1.5rem;
            border-radius: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            transition: all 0.2s;
            font-size: 1rem;
            font-family: inherit;
            width: 100%;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
        }
        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 700;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            transition: all 0.2s;
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #d4af37;
            color: #d4af37;
        }
        .stat-pill {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0.6rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: #cbd5e1;
        }
        .sql-input {
            width: 100%;
            background: #06090e;
            color: #34d399;
            font-family: 'JetBrains Mono', monospace;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #334155;
            font-size: 0.92rem;
            margin-bottom: 1rem;
            outline: none;
        }
        .sql-input:focus {
            border-color: #d4af37;
        }
        .table-badge {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Top Navigation -->
        <div class="dev-nav">
            <div class="brand">
                <span class="icon">⚡</span>
                <span>بوابة تحكم المطورين والسوبر أدمن</span>
            </div>
            <div class="nav-links">
                <a href="<?= htmlspecialchars(storefront_url('index.php')) ?>" target="_blank" class="nav-link">🌐 عرض المتجر</a>
                <a href="/admin/index.php" class="nav-link">📊 لوحة الأدمن العادية</a>
                <a href="developer.php?action=logout" class="nav-link nav-link-logout">🚪 تسجيل الخروج</a>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if ($successMsg): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 1.25rem; border-radius: 16px; margin-bottom: 2rem; font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.5rem;">🎉</span> <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 1.25rem; border-radius: 16px; margin-bottom: 2rem; font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.5rem;">⚠️</span> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <!-- ── HERO 1-CLICK SITE LOCK / UNLOCK TOGGLE ── -->
        <div class="lock-hero">
            <div class="lock-info">
                <h2>
                    <span><?= $isMaintActive ? '🔴 وضع الصيانة مُفعل (المتجر مغلق)' : '🟢 المتجر يعمل مباشرة (Online)' ?></span>
                </h2>
                <p>
                    <?= $isMaintActive 
                        ? 'المتجر حالياً مقفول أمام جميع الزوار وتظهر لهم صفحة الصيانة والتواصل بالواتساب. يمكنك كمسؤول تصفح الموقع والطلب بشكل طبيعي.' 
                        : 'المتجر حالياً مفتوح للجميع ويمكن للعملاء تصفح العطور وإتمام الطلبات بشكل طبيعي. يمكنك قفله بنقرة واحدة عند الرغبة.' ?>
                </p>
            </div>
            <div>
                <form method="post" action="developer.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                    <input type="hidden" name="form_action" value="toggle_maintenance">
                    <button type="submit" class="btn-toggle-lock">
                        <span><?= $isMaintActive ? '🔓 اضغط لفتح الموقع فـوراً للجميع' : '🔒 اضغط لقفل الموقع وتفعيل وضع الصيانة' ?></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Server Live Metrics -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem;">
            <div class="stat-pill">
                <span>📦 العطور:</span> <strong style="color:#d4af37;"><?= $totalProducts ?> عطر</strong>
            </div>
            <div class="stat-pill">
                <span>🛒 الطلبات:</span> <strong><?= $totalOrders ?> طلب</strong>
            </div>
            <div class="stat-pill">
                <span>👥 العملاء:</span> <strong><?= $totalClients ?> عميل</strong>
            </div>
            <div class="stat-pill">
                <span>🤖 بوت الواتساب:</span> <strong style="color: <?= $waBotOnline ? '#10b981' : '#f59e0b' ?>;"><?= $waBotOnline ? 'متصل بنجاح ✓' : 'جاهز للربط' ?></strong>
            </div>
            <div class="stat-pill">
                <span>🐘 PHP:</span> <strong><?= PHP_VERSION ?></strong>
            </div>
        </div>

        <!-- ── CORE DEVELOPER MODULES ── -->
        <div class="dev-grid">

            <!-- 1. 1-Click Full SQL Backup -->
            <div class="dev-card" style="border-top: 4px solid #d4af37;">
                <div>
                    <div class="dev-card-title">
                        <span>💾</span>
                        <span>أخذ نسخة احتياطية فورية (.sql)</span>
                    </div>
                    <p class="dev-card-desc">
                        توليد وتحميل ملف النسخة الاحتياطية لقاعدة البيانات بالكامل مع جميع الجداول والمنتجات والطلبات بنقرة واحدة مباشرة لجهازك.
                    </p>
                </div>
                <div>
                    <a href="developer.php?action=export_backup&csrf_token=<?= htmlspecialchars(admin_csrf_token()) ?>" class="btn-gold">
                        📥 تحميل نسخة قاعدة البيانات كاملة (.sql)
                    </a>
                </div>
            </div>

            <!-- 2. 1-Click Restore 140+ Official Perfumes -->
            <div class="dev-card" style="border-top: 4px solid #10b981;">
                <div>
                    <div class="dev-card-title">
                        <span>🔄</span>
                        <span>استرجاع البيانات الرسمية (140+ عطر)</span>
                    </div>
                    <p class="dev-card-desc">
                        استرجاع فوري لجميع العطور الأصلية (140+ عطر فاخر) والتصنيفات من الملف المعتمد <code>includes/u868008675_zein.sql</code> في ثوانٍ.
                    </p>
                </div>
                <div>
                    <form method="post" action="developer.php" onsubmit="return confirm('⚠️ هل أنت متأكد من استرجاع قاعدة البيانات الرسمية (140+ عطر)؟ سيتم تفعيل جميع المنتجات والتصنيفات الأصلية.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                        <input type="hidden" name="form_action" value="restore_official_dump">
                        <button type="submit" class="btn-gold" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff !important;">
                            ⚡ استرجاع وتفعيل قاعدة البيانات الرسمية
                        </button>
                    </form>
                </div>
            </div>

            <!-- 3. Upload & Import Custom SQL -->
            <div class="dev-card" style="border-top: 4px solid #3b82f6;">
                <div>
                    <div class="dev-card-title">
                        <span>⬆️</span>
                        <span>استيراد ملف SQL من الجهاز</span>
                    </div>
                    <p class="dev-card-desc">
                        رفع واستيراد أي ملف نسخة احتياطية <code>.sql</code> وتطبيقه على قاعدة البيانات فوراً.
                    </p>
                </div>
                <div>
                    <form method="post" action="developer.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                        <input type="hidden" name="form_action" value="import_sql_file">
                        <input type="file" name="sql_file" accept=".sql" required style="margin-bottom: 0.75rem; font-size: 0.85rem; width: 100%; color: #94a3b8;">
                        <button type="submit" class="btn-outline">
                            📤 رفع واستيراد ملف الـ SQL
                        </button>
                    </form>
                </div>
            </div>

            <!-- 4. Clear Cache & Diagnostics -->
            <div class="dev-card" style="border-top: 4px solid #8b5cf6;">
                <div>
                    <div class="dev-card-title">
                        <span>🧹</span>
                        <span>مسح كاش السيرفر و OPcache</span>
                    </div>
                    <p class="dev-card-desc">
                        إعادة تعيين كاش PHP OPcache ومسح الجلسات المؤقتة لتطبيق أي تحديثات فوراً بدون انتظار.
                    </p>
                </div>
                <div>
                    <form method="post" action="developer.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                        <input type="hidden" name="form_action" value="clear_cache">
                        <button type="submit" class="btn-outline" style="border-color: #8b5cf6; color: #c4b5fd;">
                            ⚡ مسح كاش السيرفر فوراً
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- ── DIRECT SQL CONSOLE ── -->
        <div class="dev-card" style="margin-bottom: 2.5rem;">
            <div>
                <div class="dev-card-title">
                    <span>💻</span>
                    <span>منفذ استعلامات SQL المباشر (Direct SQL Console)</span>
                </div>
                <p class="dev-card-desc">
                    تنفيذ استعلامات SQL مخصصة مباشرة على قاعدة البيانات مع إمكانية فحص النتائج وعرض البيانات.
                </p>

                <form method="post" action="developer.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                    <input type="hidden" name="form_action" value="run_sql_query">
                    <textarea name="raw_sql" rows="3" placeholder="SELECT * FROM products ORDER BY id DESC LIMIT 5;" class="sql-input"></textarea>
                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-gold" style="width: auto; padding: 0.75rem 2rem;">
                            ⚡ تشغيل الاستعلام
                        </button>
                    </div>
                </form>

                <?php if ($sqlResults !== null): ?>
                    <div style="margin-top: 1.5rem; overflow-x: auto; max-height: 400px; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;">
                        <?php if (empty($sqlResults)): ?>
                            <div style="padding: 1.5rem; text-align: center; color: #94a3b8;">لم يتم إرجاع أي صفوف.</div>
                        <?php else: ?>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: right;">
                                <thead style="background: rgba(255,255,255,0.05); border-bottom: 2px solid rgba(255,255,255,0.1);">
                                    <tr>
                                        <?php foreach (array_keys($sqlResults[0]) as $col): ?>
                                            <th style="padding: 0.75rem 1rem; color: #cbd5e1;"><?= htmlspecialchars((string)$col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sqlResults as $row): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <?php foreach ($row as $val): ?>
                                                <td style="padding: 0.6rem 1rem; color: #f8fafc;"><?= htmlspecialchars((string)($val ?? 'NULL')) ?></td>
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

        <!-- ── DATABASE TABLES EXPLORER ── -->
        <div class="dev-card">
            <div>
                <div class="dev-card-title">
                    <span>🗄️</span>
                    <span>فاحص جداول وأحجام قاعدة البيانات (Tables Explorer)</span>
                </div>
                <p class="dev-card-desc">
                    عرض إحصائيات وأحجام جميع جداول قاعدة البيانات وعدد الصفوف المحفوظة.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                    <?php foreach ($dbTables as $tbl): ?>
                        <div class="table-badge">
                            <div style="font-weight: 800; color: #fff; margin-bottom: 4px; font-size: 0.95rem;"><?= htmlspecialchars($tbl['name']) ?></div>
                            <div style="font-size: 0.82rem; color: #94a3b8;">
                                <span>الصفوف: <strong style="color:#d4af37;"><?= number_format($tbl['rows']) ?></strong></span> · 
                                <span>الحجم: <strong><?= htmlspecialchars($tbl['size']) ?></strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
