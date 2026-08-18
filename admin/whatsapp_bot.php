<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'لوحة تحكم بوت الواتساب والتأكيد الآلي';
require __DIR__ . '/_layout_start.php';

$pdo = medal_pdo();
$botUrl = 'http://127.0.0.1:3001';
if ($pdo !== null) {
    try {
        $st = $pdo->prepare("SELECT setting_value_en FROM settings WHERE setting_key = 'whatsapp_bot_url' LIMIT 1");
        $st->execute();
        $custom = $st->fetchColumn();
        if (!empty($custom)) $botUrl = rtrim($custom, '/');
    } catch (\Throwable $e) {}
}

// Check if Node bot service is reachable
$botOnline = false;
$botStatus = 'disconnected';
$ch = curl_init($botUrl . '/api/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $res) {
    $data = json_decode($res, true);
    if (!empty($data['success'])) {
        $botOnline = true;
        $botStatus = $data['status'] ?? 'unknown';
    }
}
?>

<div class="admin-header-actions" style="border-bottom: 1px solid var(--admin-card-border); padding-bottom: 1.5rem; margin-bottom: 2rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--admin-heading);">
                🤖 بوت الواتساب الذكي (whatsapp-web.js + React)
            </h1>
            <?php if ($botOnline): ?>
                <span class="admin-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 800;">
                    🟢 خدمة السيرفر متصلة
                </span>
            <?php else: ?>
                <span class="admin-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-weight: 800;">
                    🔴 خدمة البوت غير مشغلة
                </span>
            <?php endif; ?>
        </div>
        <p class="admin-muted" style="margin: 0.5rem 0 0; font-size: 0.9rem;">
            إدارة جلسة واتساب ويب، مسح كود QR، واستقبال تأكيدات الطلبات (1، 2، 3) وصور إيصالات انستاباي تلقائياً.
        </p>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= esc($botUrl) ?>" target="_blank" class="admin-btn admin-btn--primary" style="display: inline-flex; align-items: center; gap: 6px;">
            🚀 فتح لوحة React في نافذة مستقلة
        </a>
    </div>
</div>

<?php if (!$botOnline): ?>
    <div class="admin-card" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3); padding: 1.75rem; border-radius: 16px; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; color: #f59e0b; font-size: 1.15rem; font-weight: 800;">
            ⚠️ لتشغيل خدمة بوت الواتساب:
        </h3>
        <p style="font-size: 0.95rem; line-height: 1.7; color: var(--admin-heading); margin-bottom: 1rem;">
            خدمة البوت تعمل كـ Node.js Microservice على المنفذ <code>3001</code>. لتشغيلها افتح موجه الأوامر (Terminal) في مجلد <code>whatsapp_service</code> واكتب:
        </p>
        <div style="background: #0f172a; padding: 1rem 1.25rem; border-radius: 10px; font-family: monospace; direction: ltr; color: #38bdf8; font-size: 0.95rem; border: 1px solid #334155;">
            cd whatsapp_service<br>
            npm start
        </div>
        <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--admin-text-muted);">
            بمجرد التشغيل، قم بتحديث هذه الصفحة وسيظهر رمز الـ QR لمسحه بهاتفك.
        </p>
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
