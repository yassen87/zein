<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
require_admin('settings');

$pageTitle = '🤖 مركز التحكم في بوت الواتساب والتأكيد الآلي';
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

// Check if Node bot service is reachable
$botOnline = false;
$botStatus = 'disconnected';
$botInfo = null;
$botUptime = 0;

$checkUrls = [$botUrl . '/api/status', 'http://127.0.0.1:3001/api/status'];
foreach ($checkUrls as $cu) {
    $ch = curl_init($cu);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $data = json_decode($res, true);
        if ($data && isset($data['status'])) {
            $botOnline = true;
            $botStatus = $data['status'];
            $botInfo = $data['info'] ?? null;
            $botUptime = $data['uptime'] ?? 0;
            break;
        }
    }
}

// Format uptime to human readable
function formatUptime(float $seconds): string {
    if ($seconds < 60) return (int)$seconds . " ثانية";
    $mins = (int)($seconds / 60);
    if ($mins < 60) return $mins . " دقيقة";
    $hours = (int)($mins / 60);
    $minsPart = $mins % 60;
    if ($hours < 24) return $hours . " ساعة و " . $minsPart . " دقيقة";
    $days = (int)$hours / 24;
    $hoursPart = $hours % 24;
    return (int)$days . " يوم و " . $hoursPart . " ساعة";
}
?>

<!-- Premium Design Layout -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; border-bottom:1px solid #27272a; padding-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:5px; color:#f8fafc;">🤖 مركز التحكم وإعدادات بوت الواتساب</h1>
        <p class="admin-lead" style="margin-bottom:0;">التحقق من حالة الاتصال، إرسال رسائل اختبارية، وإدارة جلسة الـ QR Code</p>
    </div>
    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
        <a href="<?= esc($botUrl) ?>" target="_blank" class="admin-btn" style="background:linear-gradient(135deg, #22c55e 0%, #15803d 100%); color:#fff; border:none; padding:0.75rem 1.25rem; border-radius:10px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(34,197,94,0.25);">
            🚀 فتح لوحة البوت المستقلة ↗
        </a>
    </div>
</div>

<!-- Grid of Quick Stats & Diagnosis -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem; margin-bottom:2rem;">
    
    <!-- Connection Health -->
    <div class="admin-card" style="margin:0; padding:1.5rem; border:1px solid #27272a; background:#18181b; border-radius:14px; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
            <span style="font-size:1.3rem;">📡</span>
            <span style="font-size:0.75rem; background:#27272a; color:#a1a1aa; padding:2px 8px; border-radius:6px; font-weight:700;">SERVER CONNECTION</span>
        </div>
        <h3 style="margin:0 0 6px; font-size:0.9rem; color:#94a3b8;">حالة البوت الحالية</h3>
        <div style="font-size:1.4rem; font-weight:800; display:flex; align-items:center; gap:8px;">
            <?php if ($botOnline && $botStatus === 'ready'): ?>
                <span style="color:#10b981;">🟢 متصل وجاهز للعمل</span>
            <?php elseif ($botOnline): ?>
                <span style="color:#f59e0b;">⏳ قيد الاستعداد (<?= esc($botStatus) ?>)</span>
            <?php else: ?>
                <span style="color:#ef4444;">🔴 غير متصل</span>
            <?php endif; ?>
        </div>
        <p style="font-size:0.8rem; color:#71717a; margin-top:8px; margin-bottom:0;">مسار البوت النشط: <code><?= esc($botUrl) ?></code></p>
    </div>

    <!-- Active Phone / Instance Info -->
    <div class="admin-card" style="margin:0; padding:1.5rem; border:1px solid #27272a; background:#18181b; border-radius:14px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
            <span style="font-size:1.3rem;">📱</span>
            <span style="font-size:0.75rem; background:#27272a; color:#a1a1aa; padding:2px 8px; border-radius:6px; font-weight:700;">WHATSAPP INSTANCE</span>
        </div>
        <h3 style="margin:0 0 6px; font-size:0.9rem; color:#94a3b8;">الرقم والاسم النشط</h3>
        <div style="font-size:1.15rem; font-weight:800; color:#f4f4f5;">
            <?php if ($botInfo): ?>
                👤 <?= esc($botInfo['pushname'] ?? 'غير محدد') ?><br>
                <span style="color:#a1a1aa; font-size:0.9rem; font-weight:400;">📞 +<?= esc($botInfo['wid'] ?? '') ?></span>
            <?php else: ?>
                <span style="color:#71717a;">لا توجد جلسة نشطة حالياً</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Watchdog & Uptime -->
    <div class="admin-card" style="margin:0; padding:1.5rem; border:1px solid #27272a; background:#18181b; border-radius:14px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
            <span style="font-size:1.3rem;">⏳</span>
            <span style="font-size:0.75rem; background:#27272a; color:#a1a1aa; padding:2px 8px; border-radius:6px; font-weight:700;">UPTIME MONITOR</span>
        </div>
        <h3 style="margin:0 0 6px; font-size:0.9rem; color:#94a3b8;">مدة التشغيل المتواصل</h3>
        <div style="font-size:1.3rem; font-weight:800; color:#e4e4e7;">
            <?= $botUptime > 0 ? formatUptime($botUptime) : '0 ثانية' ?>
        </div>
        <p style="font-size:0.8rem; color:#71717a; margin-top:8px; margin-bottom:0;">الإصدار الحالي: <code>v2.6.0-Hostinger</code></p>
    </div>

</div>

<!-- Diagnostics / Test Message Tool -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem; align-items:start; flex-wrap:wrap;">
    
    <!-- Test Message Sender Form -->
    <div class="admin-card" style="margin:0; padding:1.5rem; border-radius:14px; background:#18181b; border:1px solid #27272a;">
        <h3 style="margin-top:0; font-size:1.1rem; font-weight:800; color:#f4f4f5; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span>✉️</span> إرسال رسالة اختبارية سريعة
        </h3>
        <p style="font-size:0.84rem; color:#a1a1aa; line-height:1.6; margin-bottom:1.25rem;">
            يمكنك استخدام هذه الأداة لاختبار جودة إرسال رسائل الواتساب مباشرة من استضافة هوستنجر للـ VPS والتأكد من عدم وجود حجب للاتصال.
        </p>

        <form id="test-wa-form" onsubmit="sendTestMessage(event)" class="admin-form">
            <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">
            
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:700; margin-bottom:6px; font-size:0.88rem;">رقم الهاتف (مع رمز الدولة بدون أصفار) *</label>
                <input type="text" id="test-phone" required placeholder="مثال: 201005250838" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; margin-bottom:6px; font-size:0.88rem;">محتوى رسالة الاختبار *</label>
                <textarea id="test-message" required rows="3" style="width:100%; padding:0.65rem 0.85rem; background:#09090b; border:1px solid #3f3f46; color:#fff; border-radius:8px; font-family:inherit;">هذه رسالة اختبارية لتأكيد نجاح ربط السيرفر مع الواتساب بنجاح! 🌸</textarea>
            </div>

            <button type="submit" id="btn-test-submit" style="width:100%; background:#d4af37; color:#000; border:none; padding:0.75rem; border-radius:8px; font-weight:800; cursor:pointer; font-size:0.95rem; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                🚀 إرسال الرسالة الآن
            </button>
        </form>

        <div id="test-result-box" style="margin-top:1rem; padding:0.8rem; border-radius:8px; font-size:0.85rem; display:none;"></div>
    </div>

    <!-- Troubleshooting Guide -->
    <div class="admin-card" style="margin:0; padding:1.5rem; border-radius:14px; background:#18181b; border:1px solid #27272a;">
        <h3 style="margin-top:0; font-size:1.1rem; font-weight:800; color:#f4f4f5; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span>🛠️</span> تشخيص واستكشاف الأخطاء
        </h3>
        
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="background:#27272a; padding:0.8rem; border-radius:8px;">
                <strong style="color:#f59e0b; display:block; margin-bottom:4px; font-size:0.88rem;">1. هل البوت متصل بالواتساب والـ QR كود؟</strong>
                <span style="font-size:0.82rem; color:#a1a1aa; line-height:1.5; display:block;">
                    تأكد من فتح لوحة البوت المدمجة أدناه ومسح رمز الـ QR بالهاتف لتفعيل الخدمة.
                </span>
            </div>

            <div style="background:#27272a; padding:0.8rem; border-radius:8px;">
                <strong style="color:#f59e0b; display:block; margin-bottom:4px; font-size:0.88rem;">2. كيف أعيد تشغيل البوت البعيد بالكامل؟</strong>
                <span style="font-size:0.82rem; color:#a1a1aa; line-height:1.5; display:block;">
                    ادخل للـ VPS عبر الـ Terminal وشغّل هذا الأمر لإعادة تهيئة الجلسة:
                    <code style="display:block; margin-top:5px; background:#09090b; padding:4px 8px; border-radius:4px; color:#38bdf8; direction:ltr;">pm2 restart whatsapp-bot</code>
                </span>
            </div>

            <div style="background:#27272a; padding:0.8rem; border-radius:8px;">
                <strong style="color:#f59e0b; display:block; margin-bottom:4px; font-size:0.88rem;">3. تفعيل جدار الحماية وعنوان الـ IP</strong>
                <span style="font-size:0.82rem; color:#a1a1aa; line-height:1.5; display:block;">
                    تأكد أن منفذ الـ VPS (البورت 3001) مفتوح ومسموح بمرور البيانات له، وأن اسم النطاق <code>wa.zeinperfumes.com</code> يشير للـ VPS بشكل سليم.
                </span>
            </div>
        </div>
    </div>

</div>

<!-- Embedded React WhatsApp Dashboard Iframe -->
<div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid var(--admin-card-border); height: 750px;">
    <div style="background:#18181b; padding:10px 1.25rem; border-bottom:1px solid #27272a; font-weight:700; color:#f4f4f5; display:flex; align-items:center; gap:8px;">
        <span>📲</span> لوحة إدارة الجلسة المباشرة (QR Scanner)
    </div>
    <iframe 
        src="<?= esc($botUrl) ?>" 
        style="width: 100%; height: calc(100% - 45px); border: none; background: #0b1120;"
        title="WhatsApp Bot React Dashboard">
    </iframe>
</div>

<script>
async function sendTestMessage(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-test-submit');
    const resultBox = document.getElementById('test-result-box');
    const phone = document.getElementById('test-phone').value;
    const message = document.getElementById('test-message').value;

    btn.disabled = true;
    btn.innerHTML = '⏳ جاري الإرسال الفوري...';
    resultBox.style.display = 'none';

    try {
        const formData = new FormData();
        formData.append('csrf', '<?= esc(admin_csrf_token()) ?>');
        formData.append('phone', phone);
        formData.append('message', message);

        const res = await fetch('<?= esc(admin_url("ajax_test_wa_message.php")) ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        resultBox.style.display = 'block';
        if (data.success) {
            resultBox.style.background = 'rgba(16, 185, 129, 0.1)';
            resultBox.style.border = '1px solid #10b981';
            resultBox.style.color = '#10b981';
            resultBox.innerHTML = '✅ <strong>نجاح الإرسال:</strong> تم تسليم الرسالة الاختبارية للبوت وإرسالها للمشترك بنجاح!';
        } else {
            resultBox.style.background = 'rgba(239, 68, 68, 0.1)';
            resultBox.style.border = '1px solid #ef4444';
            resultBox.style.color = '#ef4444';
            resultBox.innerHTML = '❌ <strong>خطأ في الإرسال:</strong> ' + (data.error || 'فشلت عملية الإرسال');
        }
    } catch (err) {
        resultBox.style.display = 'block';
        resultBox.style.background = 'rgba(245, 158, 11, 0.1)';
        resultBox.style.border = '1px solid #f59e0b';
        resultBox.style.color = '#f59e0b';
        resultBox.innerHTML = '⚠️ <strong>خطأ شبكة:</strong> تعذر الوصول لسيرفر الواتساب. تأكد من عمل الـ VPS.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '🚀 إرسال الرسالة الآن';
    }
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
