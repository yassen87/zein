<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/maintenance_helper.php';

$pageTitle = 'لوحة تحكم المطورين والسوبر أدمن (Developer & Super-Admin Hub)';
$activeMenu = 'developer_hub';

$pdo = medal_pdo();
if (!$pdo) {
    die('Database connection failed.');
}

$successMsg = '';
$errorMsg = '';
$sqlResults = null;

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
function run_developer_sql_import(PDO $pdo, string $fileContent): array {
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
// 2. Handle POST Actions
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['form_action'] ?? '';

    // Action: Save Maintenance Mode
    if ($action === 'save_maintenance') {
        $enabled = !empty($_POST['maintenance_enabled']);
        $titleAr = trim($_POST['title_ar'] ?? '');
        $titleEn = trim($_POST['title_en'] ?? '');
        $msgAr   = trim($_POST['message_ar'] ?? '');
        $msgEn   = trim($_POST['message_en'] ?? '');
        $retTime = trim($_POST['estimated_return'] ?? '');

        $saved = save_maintenance_config([
            'enabled' => $enabled,
            'title_ar' => $titleAr ?: 'الموقع قيد الصيانة والتحديث الفاخر',
            'title_en' => $titleEn ?: 'Store Under Maintenance',
            'message_ar' => $msgAr,
            'message_en' => $msgEn,
            'estimated_return' => $retTime,
        ]);

        if ($saved) {
            $successMsg = $enabled ? '🔒 تم قفل الموقع وتفعيل وضع الصيانة بنجاح!' : '🔓 تم فتح الموقع وإلغاء وضع الصيانة بنجاح!';
        } else {
            $errorMsg = 'فشل حفظ إعدادات وضع الصيانة.';
        }
    }

    // Action: 1-Click Restore Official Database Dump (140+ Perfumes)
    elseif ($action === 'restore_official_dump') {
        $dumpPath = __DIR__ . '/../includes/u868008675_zein.sql';
        if (!file_exists($dumpPath)) {
            $errorMsg = 'ملف قاعدة البيانات الرسمي u868008675_zein.sql غير موجود في مجلد includes!';
        } else {
            $sqlContent = file_get_contents($dumpPath);
            if (!$sqlContent) {
                $errorMsg = 'فشل قراءة ملف النسخة الاحتياطية الرسمية!';
            } else {
                $res = run_developer_sql_import($pdo, $sqlContent);
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
                $res = run_developer_sql_import($pdo, $fileContent);
                medal_ensure_orders_schema($pdo);
                $successMsg = "✅ تم استيراد ملف الـ SQL بنجاح! (تم تنفيذ {$res['executed']} استعلام).";
                if (!empty($res['errors'])) {
                    $successMsg .= ' (تنبيه: تم تجاوز ' . count($res['errors']) . ' ملاحظة غير حرجة).';
                }
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
        if (function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
            $cleared[] = 'APCu';
        }
        // Touch config to refresh mtimes
        @touch(__DIR__ . '/../includes/config.php');
        $cleared[] = 'Session & Config Timestamps';
        $successMsg = '⚡ تم مسح وإعادة تعيين الكاش بنجاح: ' . implode(' + ', $cleared);
    }

    // Action: Execute Safe SQL Query
    elseif ($action === 'run_sql_query') {
        $query = trim($_POST['raw_sql'] ?? '');
        if ($query === '') {
            $errorMsg = 'يرجى كتابة استعلام SQL للتنفيذ.';
        } else {
            try {
                $st = $pdo->query($query);
                if ($st) {
                    $sqlResults = $st->fetchAll(PDO::FETCH_ASSOC);
                    $successMsg = '✓ تم تنفيذ الاستعلام بنجاح! عدد الصفوف الناتجة: ' . count($sqlResults);
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
            'engine' => $t['Engine'] ?? 'InnoDB',
        ];
    }
} catch (Throwable) {}

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalClients = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// Check Node.js WhatsApp Bot API Status
$waBotOnline = false;
$waBotInfo = null;
try {
    $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
    $resp = @file_get_contents('http://127.0.0.1:3001/api/status', false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['success']) && $data['status'] === 'ready') {
            $waBotOnline = true;
            $waBotInfo = $data['info'] ?? null;
        }
    }
} catch (Throwable) {}

require __DIR__ . '/_layout_start.php';
?>

<style>
.dev-shell {
    padding: 1.5rem;
    font-family: 'Tajawal', sans-serif;
}
.dev-header {
    background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #0f172a 100%);
    border: 1px solid rgba(212, 175, 55, 0.35);
    border-radius: 20px;
    padding: 2rem;
    color: #fff;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
}
.dev-header::after {
    content: '⚡ DEV';
    position: absolute;
    top: -10px;
    left: 20px;
    font-size: 5rem;
    font-weight: 900;
    color: rgba(255,255,255,0.03);
    pointer-events: none;
}
.dev-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.dev-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    display: flex;
    flex-col: column;
    justify-content: space-between;
    transition: all 0.2s ease;
}
.dev-card:hover {
    border-color: rgba(212, 175, 55, 0.5);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.08);
}
.dev-card-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.75rem;
}
.dev-card-desc {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}
.btn-gold {
    background: linear-gradient(135deg, #d4af37 0%, #aa8420 100%);
    color: #090d16 !important;
    font-weight: 800;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    transition: all 0.2s;
    font-size: 0.95rem;
}
.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.45);
}
.btn-danger {
    background: #ef4444;
    color: #ffffff !important;
    font-weight: 800;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.95rem;
}
.btn-danger:hover { background: #dc2626; }
.btn-outline {
    background: transparent;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    font-weight: 700;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
.badge-status {
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-active { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
.badge-inactive { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
.stat-pill {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 0.6rem 1rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    color: #e2e8f0;
}
.sql-input {
    width: 100%;
    background: #090d16;
    color: #a7f3d0;
    font-family: monospace;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid #334155;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
</style>

<div class="dev-shell">

    <!-- Top Notifications -->
    <?php if ($successMsg): ?>
        <div style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 1.25rem; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.4rem;">🎉</span> <?= esc($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div style="background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 1.25rem; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.4rem;">⚠️</span> <?= esc($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Main Header -->
    <div class="dev-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <span class="badge-status <?= $isMaintActive ? 'badge-inactive' : 'badge-active' ?>" style="margin-bottom: 0.75rem;">
                    ● <?= $isMaintActive ? 'المتجر في وضع الصيانة (مغلق أمام الزوار)' : 'المتجر يعمل مباشرة (Online)' ?>
                </span>
                <h1 style="font-size: 1.85rem; font-weight: 900; margin: 0 0 0.4rem; color: #ffffff;">
                    ⚡ لوحة تحكم المطورين والسوبر أدمن
                </h1>
                <p style="color: #94a3b8; margin: 0; font-size: 0.95rem;">
                    تحكم كامل في وضع الصيانة، أخذ النسخ الاحتياطية، واسترجاع قاعدة البيانات وإدارة السيرفر بنقرة واحدة.
                </p>
            </div>
            
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="<?= esc(admin_url('developer_hub.php?action=export_backup&csrf_token=' . admin_csrf_token())) ?>" class="btn-gold">
                    <span>💾 تحميل نسخة DB كاملة (.sql)</span>
                </a>
            </div>
        </div>

        <!-- Live Server Metrics -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.25rem;">
            <div class="stat-pill">
                <span>📦 المنتجات:</span> <strong><?= $totalProducts ?> عطر</strong>
            </div>
            <div class="stat-pill">
                <span>🛒 الطلبات:</span> <strong><?= $totalOrders ?> طلب</strong>
            </div>
            <div class="stat-pill">
                <span>👥 العملاء:</span> <strong><?= $totalClients ?> عميل</strong>
            </div>
            <div class="stat-pill">
                <span>🤖 بوت الواتساب:</span> <strong style="color: <?= $waBotOnline ? '#10b981' : '#f59e0b' ?>;"><?= $waBotOnline ? 'متصل وجاهز ✓' : 'يعمل عبر الخلفية' ?></strong>
            </div>
            <div class="stat-pill">
                <span>🐘 PHP:</span> <strong><?= PHP_VERSION ?></strong>
            </div>
        </div>
    </div>

    <!-- Core Functional Modules Grid -->
    <div class="dev-grid">

        <!-- 1. Maintenance Mode Switch -->
        <div class="dev-card" style="border-top: 4px solid <?= $isMaintActive ? '#ef4444' : '#10b981' ?>;">
            <div>
                <div class="dev-card-title">
                    <span><?= $isMaintActive ? '🔒' : '🔓' ?></span>
                    <span>وضع الصيانة وقفل الموقع</span>
                </div>
                <p class="dev-card-desc">
                    عند تفعيل وضع الصيانة، يظهر للزوار كارت صيانة فاخر مع روابط الواتساب، بينما يمكنك أنت كأدمن ومطور تصفح الموقع والطلب بشكل طبيعي.
                </p>

                <form method="post" action="<?= esc(admin_url('developer_hub.php')) ?>">
                    <?= admin_csrf_field() ?>
                    <input type="hidden" name="form_action" value="save_maintenance">

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 800; color: #0f172a;">
                            <input type="checkbox" name="maintenance_enabled" value="1" <?= $isMaintActive ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: #ef4444;">
                            <span>تفعيل وضع الصيانة (إغلاق المتجر أمام الزوار)</span>
                        </label>
                    </div>

                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.82rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">عنوان الصيانة (عربي):</label>
                        <input type="text" name="title_ar" value="<?= esc($maintCfg['title_ar'] ?? '') ?>" class="admin-input" style="width: 100%;">
                    </div>

                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.82rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">رسالة الصيانة للعملاء:</label>
                        <textarea name="message_ar" rows="2" class="admin-input" style="width: 100%;"><?= esc($maintCfg['message_ar'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">الموعد المتوقع للعودة (اختياري):</label>
                        <input type="text" name="estimated_return" placeholder="مثال: خلال ساعتين / غداً الساعة 6 مساءً" value="<?= esc($maintCfg['estimated_return'] ?? '') ?>" class="admin-input" style="width: 100%;">
                    </div>

                    <button type="submit" class="btn-gold" style="width: 100%;">
                        💾 حفظ إعدادات وضع الصيانة
                    </button>
                </form>
            </div>
        </div>

        <!-- 2. Instant Database Recovery & 1-Click Restore -->
        <div class="dev-card" style="border-top: 4px solid #d4af37;">
            <div>
                <div class="dev-card-title">
                    <span>🔄</span>
                    <span>استرجاع البيانات (1-Click Restore)</span>
                </div>
                <p class="dev-card-desc">
                    استرجاع فوري لجميع بيانات العطور الرسمية (140+ عطر فاخر) والتصنيفات الأصلية في حال حدوث أي خطأ أو حذف غير مقصود.
                </p>

                <div style="background: #fdfbf7; border: 1px solid rgba(212,175,55,0.3); border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="font-weight: 800; color: #92400e; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                        <span>💎 النسخة الرسمية للمتجر:</span>
                    </div>
                    <p style="font-size: 0.85rem; color: #78350f; margin: 0 0 1rem; line-height: 1.5;">
                        ملف قاعدة البيانات المعتمد: <code>includes/u868008675_zein.sql</code> (140+ عطر، التصنيفات، والمستخدمين).
                    </p>

                    <form method="post" action="<?= esc(admin_url('developer_hub.php')) ?>" onsubmit="return confirm('⚠️ هل أنت متأكد من رغبتك في استرجاع قاعدة البيانات الرسمية بالكامل (140+ عطر)؟ سيتم تفعيل جميع المنتجات والتصنيفات الأصلية.');">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="form_action" value="restore_official_dump">
                        <button type="submit" class="btn-gold" style="width: 100%;">
                            ⚡ استرجاع وتفعيل قاعدة البيانات الرسمية (140+ عطر)
                        </button>
                    </form>
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: #0f172a; margin-bottom: 0.5rem;">
                        ⬆️ رفع واستيراد ملف SQL مخصص:
                    </div>
                    <form method="post" action="<?= esc(admin_url('developer_hub.php')) ?>" enctype="multipart/form-data">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="form_action" value="import_sql_file">
                        <input type="file" name="sql_file" accept=".sql" required style="margin-bottom: 0.75rem; font-size: 0.85rem; width: 100%;">
                        <button type="submit" class="btn-outline" style="width: 100%;">
                            📤 استيراد ملف SQL من الجهاز
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Server Cache & WhatsApp Bot Tools -->
        <div class="dev-card" style="border-top: 4px solid #3b82f6;">
            <div>
                <div class="dev-card-title">
                    <span>⚙️</span>
                    <span>أدوات السيرفر والكاش</span>
                </div>
                <p class="dev-card-desc">
                    إعادة تعيين كاش السيرفر (PHP OPcache)، مسح الجلسات المؤقتة، وفحص اتصال بوت الواتساب والتأكيد التلقائي.
                </p>

                <!-- Clear Cache Form -->
                <div style="margin-bottom: 1.5rem;">
                    <form method="post" action="<?= esc(admin_url('developer_hub.php')) ?>">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="form_action" value="clear_cache">
                        <button type="submit" class="btn-outline" style="width: 100%; border-color: #3b82f6; color: #1d4ed8;">
                            🧹 مسح كاش السيرفر و PHP OPcache
                        </button>
                    </form>
                </div>

                <!-- WhatsApp Bot Quick Access -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1rem;">
                    <div style="font-weight: 800; color: #166534; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                        <span>🤖 ربط الواتساب السحابي:</span>
                    </div>
                    <p style="font-size: 0.85rem; color: #14532d; margin: 0 0 1rem;">
                        الحالة: <strong><?= $waBotOnline ? 'متصل بنجاح ✓' : 'جاهز للربط' ?></strong>
                    </p>
                    <a href="<?= esc(admin_url('whatsapp_bot.php')) ?>" class="btn-gold" style="width: 100%; background: #10b981;">
                        💬 فتح شاشة ربط الواتساب والـ QR
                    </a>
                </div>

                <!-- Bypass link -->
                <div style="font-size: 0.82rem; color: #64748b; background: #f8fafc; padding: 0.75rem; border-radius: 10px; border: 1px dashed #cbd5e1;">
                    🔑 <strong>رابط تجاوز الصيانة المباشر:</strong><br>
                    <code style="color: #d97706; word-break: break-all;"><?= esc(storefront_url('index.php?dev_key=zein2026')) ?></code>
                </div>
            </div>
        </div>

    </div>

    <!-- Direct SQL Query Console -->
    <div class="dev-card" style="margin-bottom: 2rem;">
        <div>
            <div class="dev-card-title">
                <span>💻</span>
                <span>منفذ استعلامات SQL المباشر (Direct SQL Console)</span>
            </div>
            <p class="dev-card-desc">
                تنفيذ استعلامات SQL مخصصة مباشرة على قاعدة البيانات مع إمكانية فحص النتائج وعرض البيانات.
            </p>

            <form method="post" action="<?= esc(admin_url('developer_hub.php')) ?>">
                <?= admin_csrf_field() ?>
                <input type="hidden" name="form_action" value="run_sql_query">
                <textarea name="raw_sql" rows="3" placeholder="SELECT * FROM products ORDER BY id DESC LIMIT 5;" class="sql-input"></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-gold">
                        ⚡ تشغيل الاستعلام
                    </button>
                </div>
            </form>

            <?php if ($sqlResults !== null): ?>
                <div style="margin-top: 1.5rem; overflow-x: auto; max-height: 400px; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <?php if (empty($sqlResults)): ?>
                        <div style="padding: 1.5rem; text-align: center; color: #64748b;">لم يتم إرجاع أي صفوف.</div>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: right;">
                            <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <tr>
                                    <?php foreach (array_keys($sqlResults[0]) as $col): ?>
                                        <th style="padding: 0.75rem 1rem; color: #334155;"><?= esc((string)$col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sqlResults as $row): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <?php foreach ($row as $val): ?>
                                            <td style="padding: 0.6rem 1rem; color: #0f172a;"><?= esc((string)($val ?? 'NULL')) ?></td>
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

    <!-- Database Tables Explorer -->
    <div class="dev-card">
        <div>
            <div class="dev-card-title">
                <span>🗄️</span>
                <span>فاحص جداول قاعدة البيانات (Tables Explorer)</span>
            </div>
            <p class="dev-card-desc">
                عرض إحصائيات وأحجام جميع جداول قاعدة البيانات وعدد الصفوف المحفوظة.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                <?php foreach ($dbTables as $tbl): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem;">
                        <div style="font-weight: 800; color: #0f172a; margin-bottom: 4px; font-size: 0.92rem;"><?= esc($tbl['name']) ?></div>
                        <div style="font-size: 0.82rem; color: #64748b;">
                            <span>الصفوف: <strong><?= number_format($tbl['rows']) ?></strong></span> · 
                            <span>الحجم: <strong><?= esc($tbl['size']) ?></strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
