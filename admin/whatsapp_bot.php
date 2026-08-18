<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'لوحة تحكم بوت الواتساب والتأكيد الآلي';
require __DIR__ . '/_layout_start.php';

$pdo = medal_pdo();
$botUrl = 'https://wa.zeinperfumes.com';
if ($pdo !== null) {
    try {
        $st = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
        $st->execute();
        $custom = $st->fetchColumn();
        if (!empty($custom)) $botUrl = rtrim($custom, '/');
    } catch (\Throwable $e) {}
}

// Check if Node bot service is reachable (try local port first then public URL)
$botOnline = false;
$botStatus = 'disconnected';

$checkUrls = ['http://127.0.0.1:3001/api/status', $botUrl . '/api/status'];
foreach ($checkUrls as $cu) {
    $ch = curl_init($cu);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $data = json_decode($res, true);
        if (!empty($data['success'])) {
            $botOnline = true;
            $botStatus = $data['status'] ?? 'ready';
            break;
        }
    }
}
?>

<div class="admin-header-actions" style="border-bottom: 1px solid var(--admin-card-border); padding-bottom: 1.5rem; margin-bottom: 2rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--admin-heading);">
                🤖 لوحة تحكم بوت الواتساب والتأكيد الآلي
            </h1>
            <?php if ($botOnline): ?>
                <span class="admin-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 800;">
                    🟢 خدمة البوت متصلة ونشطة
                </span>
            <?php else: ?>
                <span class="admin-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-weight: 800;">
                    🔴 خدمة البوت قيد الانتظار
                </span>
            <?php endif; ?>
        </div>
        <p class="admin-muted" style="margin: 0.5rem 0 0; font-size: 0.9rem;">
            الرابط المباشر للوحة البوت: <a href="https://wa.zeinperfumes.com/" target="_blank" style="color:#d4af37; font-weight:700;">https://wa.zeinperfumes.com/</a>
        </p>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= esc($botUrl) ?>" target="_blank" class="admin-btn admin-btn--primary" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #d4af37 0%, #b45309 100%); color:#fff; border:none; padding:0.75rem 1.25rem; border-radius:10px; font-weight:800; text-decoration:none;">
            🚀 فتح لوحة البوت (wa.zeinperfumes.com) في نافذة مستقلة ↗
        </a>
    </div>
</div>

<?php if (!$botOnline): ?>
    <div class="admin-card" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3); padding: 1.75rem; border-radius: 16px; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; color: #f59e0b; font-size: 1.15rem; font-weight: 800;">
            ⚠️ لتشغيل وتفعيل خدمة بوت الواتساب على السيرفر:
        </h3>
        <p style="font-size: 0.95rem; line-height: 1.7; color: var(--admin-heading); margin-bottom: 1rem;">
            اللوحة متصلة بالرابط: <code>https://wa.zeinperfumes.com/</code>. لتشغيل البوت في الخلفية 24/7 عبر الـ SSH:
        </p>
        <div style="background: #0f172a; padding: 1rem 1.25rem; border-radius: 10px; font-family: monospace; direction: ltr; color: #38bdf8; font-size: 0.95rem; border: 1px solid #334155;">
            cd /var/www/zein/whatsapp_service<br>
            pm2 start server.js --name "zein-whatsapp"
        </div>
    </div>
<?php endif; ?>

<!-- Embedded React WhatsApp Dashboard Iframe -->
<div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid var(--admin-card-border); height: 780px;">
    <iframe 
        src="<?= esc($botUrl) ?>" 
        style="width: 100%; height: 100%; border: none; background: #0b1120;"
        title="WhatsApp Bot React Dashboard">
    </iframe>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
