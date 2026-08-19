<?php
declare(strict_types=1);

/**
 * Maintenance Mode Helper for Zain Perfumes
 */

function get_maintenance_file_path(): string {
    return __DIR__ . '/maintenance.json';
}

function get_maintenance_config(): array {
    $file = get_maintenance_file_path();
    $default = [
        'enabled' => false,
        'title_ar' => 'الموقع قيد الصيانة والتحديث الفاخر',
        'title_en' => 'Store Under Luxury Maintenance',
        'message_ar' => 'نقوم حالياً بتحديث متجر زين للعطور وإضافة تشكيلات جديدة حصرية ورفع مستوى الخدمة. سنعود للعمل بكامل طاقتنا قريباً جداً!',
        'message_en' => 'We are currently updating Zain Perfumes store with exclusive new collections. We will be back shortly!',
        'estimated_return' => '',
        'contact_phone' => '011141058632',
        'contact_whatsapp' => '2011141058632',
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!file_exists($file)) {
        return $default;
    }

    $content = @file_get_contents($file);
    if (!$content) {
        return $default;
    }

    $data = json_decode($content, true);
    if (!is_array($data)) {
        return $default;
    }

    return array_merge($default, $data);
}

function save_maintenance_config(array $config): bool {
    $file = get_maintenance_file_path();
    $current = get_maintenance_config();
    $merged = array_merge($current, $config);
    $merged['updated_at'] = date('Y-m-d H:i:s');
    return (bool) @file_put_contents($file, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function is_maintenance_enabled(): bool {
    $cfg = get_maintenance_config();
    return !empty($cfg['enabled']);
}

function check_and_enforce_maintenance(): void {
    if (!is_maintenance_enabled()) {
        return;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // ALWAYS allow developer portal and admin panel so you can NEVER get locked out
    if (
        strpos($uri, '/admin/') !== false || 
        strpos($uri, 'developer.php') !== false || 
        $script === 'developer.php'
    ) {
        return;
    }

    // Check admin session bypass
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (
        !empty($_SESSION['medal_admin_id']) || 
        !empty($_SESSION['admin_logged_in']) || 
        !empty($_SESSION['admin_user']) || 
        !empty($_SESSION['admin_id'])
    ) {
        $GLOBALS['is_maintenance_admin_bypass'] = true;
        return;
    }

    // Secret dev key bypass: e.g. ?dev_key=zein2026
    if (isset($_GET['dev_key']) && $_GET['dev_key'] === 'zein2026') {
        $_SESSION['dev_bypass_active'] = true;
        $GLOBALS['is_maintenance_admin_bypass'] = true;
        return;
    }

    if (!empty($_SESSION['dev_bypass_active'])) {
        $GLOBALS['is_maintenance_admin_bypass'] = true;
        return;
    }

    // Render maintenance page and exit
    render_maintenance_screen();
    exit;
}

function render_maintenance_screen(): void {
    $cfg = get_maintenance_config();
    $isAr = (!isset($_GET['lang']) || $_GET['lang'] === 'ar');
    $title = $isAr ? $cfg['title_ar'] : $cfg['title_en'];
    $message = $isAr ? $cfg['message_ar'] : $cfg['message_en'];
    $waPhone = preg_replace('/\D/', '', $cfg['contact_whatsapp'] ?: '2011141058632');
    $waUrl = "https://wa.me/{$waPhone}?text=" . urlencode($isAr ? 'مرحباً، أستفسر عن موعد عودة متجر زين للعطور للعمل' : 'Hello, inquiring about Zain Perfumes store maintenance');

    http_response_code(503);
    header('Retry-After: 3600');
    ?>
    <!DOCTYPE html>
    <html lang="<?= $isAr ? 'ar' : 'en' ?>" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> — زين للعطور</title>
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
                overflow-x: hidden;
            }
            .bg-glow {
                position: absolute;
                width: 600px;
                height: 600px;
                background: radial-gradient(circle, rgba(212, 175, 55, 0.12) 0%, rgba(9, 13, 22, 0) 70%);
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
                z-index: 0;
            }
            .maintenance-card {
                background: rgba(15, 23, 42, 0.75);
                border: 1px solid rgba(212, 175, 55, 0.35);
                backdrop-filter: blur(16px);
                border-radius: 28px;
                padding: 3.5rem 2.5rem;
                max-width: 620px;
                width: 100%;
                text-align: center;
                position: relative;
                z-index: 1;
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(212, 175, 55, 0.1);
            }
            .logo-wrap {
                width: 88px;
                height: 88px;
                background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.05) 100%);
                border: 2px solid #d4af37;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.75rem;
                font-size: 2.75rem;
                box-shadow: 0 0 30px rgba(212, 175, 55, 0.35);
                animation: pulse-glow 3s infinite alternate;
            }
            @keyframes pulse-glow {
                0% { box-shadow: 0 0 20px rgba(212, 175, 55, 0.2); transform: scale(1); }
                100% { box-shadow: 0 0 40px rgba(212, 175, 55, 0.5); transform: scale(1.04); }
            }
            .gold-badge {
                display: inline-block;
                background: linear-gradient(135deg, #d4af37, #996515);
                color: #090d16;
                font-weight: 800;
                font-size: 0.85rem;
                padding: 0.35rem 1.25rem;
                border-radius: 50px;
                margin-bottom: 1.25rem;
                letter-spacing: 0.5px;
            }
            h1 {
                font-size: 2.1rem;
                font-weight: 900;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #ffffff 40%, #d4af37 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                line-height: 1.3;
            }
            p {
                color: #94a3b8;
                font-size: 1.05rem;
                line-height: 1.8;
                margin-bottom: 2rem;
            }
            .time-box {
                background: rgba(30, 41, 59, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 16px;
                padding: 1rem 1.5rem;
                margin-bottom: 2.25rem;
                display: inline-flex;
                align-items: center;
                gap: 12px;
                color: #e2e8f0;
                font-size: 0.95rem;
            }
            .btn-wa {
                background: #25d366;
                color: #ffffff;
                text-decoration: none;
                font-weight: 800;
                font-size: 1.05rem;
                padding: 1rem 2.25rem;
                border-radius: 50px;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
                transition: all 0.25s ease;
            }
            .btn-wa:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 25px rgba(37, 211, 102, 0.6);
            }
            .admin-link {
                display: block;
                margin-top: 2.5rem;
                color: #64748b;
                text-decoration: none;
                font-size: 0.82rem;
                transition: color 0.2s;
            }
            .admin-link:hover { color: #d4af37; }
        </style>
    </head>
    <body>
        <div class="bg-glow"></div>
        <div class="maintenance-card">
            <div class="logo-wrap">👑</div>
            <div class="gold-badge"><?= $isAr ? 'وضع الصيانة والتطوير' : 'Maintenance Mode' ?></div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= nl2br(htmlspecialchars($message)) ?></p>

            <?php if (!empty($cfg['estimated_return'])): ?>
                <div class="time-box">
                    <span>⏳</span>
                    <span><?= $isAr ? 'الموعد المتوقع للعودة:' : 'Estimated Launch:' ?> <strong><?= htmlspecialchars($cfg['estimated_return']) ?></strong></span>
                </div>
            <?php endif; ?>

            <div>
                <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" class="btn-wa">
                    <span>💬 <?= $isAr ? 'تواصل معنا عبر الواتساب' : 'Contact via WhatsApp' ?></span>
                </a>
            </div>

            <div style="display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-top: 2rem;">
                <a href="/developer.php" class="admin-link">⚡ <?= $isAr ? 'بوابة تحكم المطورين' : 'Developer Portal' ?></a>
                <a href="/admin/login.php" class="admin-link">🔒 <?= $isAr ? 'تسجيل دخول الإدارة' : 'Admin Login' ?></a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
