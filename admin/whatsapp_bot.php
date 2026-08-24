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

// Fetch uploaded receipts directly from Hostinger DB
$receiptsList = [];
if ($pdo !== null) {
    try {
        $rSt = $pdo->prepare("SELECT id, order_number, customer_name, customer_phone, total, payment_method, payment_receipt, payment_reference, payment_status, created_at 
                              FROM orders 
                              WHERE (payment_receipt IS NOT NULL AND payment_receipt != '') 
                                 OR (payment_reference IS NOT NULL AND payment_reference != '') 
                              ORDER BY id DESC LIMIT 50");
        $rSt->execute();
        $receiptsList = $rSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    $days = (int)($hours / 24);
    $hoursPart = $hours % 24;
    return (int)$days . " يوم و " . $hoursPart . " ساعة";
}
?>

<!-- Premium Design Layout -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; border-bottom:1px solid #27272a; padding-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:5px; color:#f8fafc;">🤖 مركز التحكم وإعدادات بوت الواتساب</h1>
        <p class="admin-lead" style="margin-bottom:0;">التحقق من حالة الاتصال، إرسال رسائل اختبارية، واستعراض إيصالات التحويل</p>
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
            <span style="font-size:1.3rem;">🖼️</span>
            <span style="font-size:0.75rem; background:#27272a; color:#a1a1aa; padding:2px 8px; border-radius:6px; font-weight:700;">RECEIPTS GALLERY</span>
        </div>
        <h3 style="margin:0 0 6px; font-size:0.9rem; color:#94a3b8;">إجمالي الإيصالات المستلمة</h3>
        <div style="font-size:1.4rem; font-weight:800; color:#d4af37;">
            <?= count($receiptsList) ?> إيصال تحويل 📸
        </div>
        <p style="font-size:0.8rem; color:#71717a; margin-top:8px; margin-bottom:0;">من المتجر والموقع مباشرة</p>
    </div>

</div>

<!-- Direct Hostinger Receipt Gallery Section -->
<div class="admin-card" style="margin-bottom:2rem; padding:1.5rem; border-radius:16px; background:#18181b; border:1px solid #27272a;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid #27272a; padding-bottom:1rem;">
        <div>
            <h3 style="margin:0; font-size:1.2rem; font-weight:800; color:#f4f4f5; display:flex; align-items:center; gap:8px;">
                <span>📸</span> معرض إيصالات التحويل المستلمة (تحديث مباشر)
            </h3>
            <p style="margin:4px 0 0; font-size:0.85rem; color:#a1a1aa;">
                استعراض كافة الإيصالات المرفوعة من العملاء عند التشيك أوت أو المحولة عبر الواتساب
            </p>
        </div>
        <span style="font-size:0.8rem; background:rgba(212,175,55,0.15); color:#d4af37; font-weight:800; padding:4px 12px; border-radius:20px; border:1px solid rgba(212,175,55,0.3);">
            <?= count($receiptsList) ?> صورة
        </span>
    </div>

    <?php if (empty($receiptsList)): ?>
        <div style="text-align:center; padding:3rem 1rem; background:#09090b; border-radius:12px; border:1px dashed #27272a;">
            <div style="font-size:2.5rem; margin-bottom:0.5rem;">📥</div>
            <h4 style="margin:0 0 6px; font-size:1rem; color:#f4f4f5;">لا توجد إيصالات تحويل مرفوعة حتى الآن</h4>
            <p style="margin:0; font-size:0.85rem; color:#71717a;">ستظهر صور تحويل العطور فور رفعها عبر صفحة الدفع أو محادثات البوت هنا تلقائياً</p>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1.25rem;">
            <?php foreach ($receiptsList as $rc): ?>
                <?php 
                    $cleanFile = ltrim((string)$rc['payment_receipt'], '/');
                    $cleanFile = preg_replace('/^assets\/uploads\/receipts\//', '', $cleanFile);
                    $imgUrl = !empty($cleanFile) ? url('assets/uploads/receipts/' . $cleanFile) : '';
                ?>
                <div style="background:#09090b; border:1px solid #27272a; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; transition:all 0.2s ease;">
                    <div style="height:180px; background:#18181b; position:relative; overflow:hidden; display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="openReceiptModal('<?= esc($imgUrl) ?>', '<?= esc((string)$rc['order_number']) ?>', '<?= esc((string)$rc['customer_name']) ?>', '<?= esc((string)$rc['customer_phone']) ?>')">
                        <?php if (!empty($imgUrl)): ?>
                            <img src="<?= esc($imgUrl) ?>" alt="Receipt" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="text-align:center; color:#a1a1aa; font-size:0.85rem;">
                                🔢 رقم عملية مرجعي:<br>
                                <strong style="color:#d4af37; font-size:0.95rem; font-family:monospace;"><?= esc((string)$rc['payment_reference']) ?></strong>
                            </div>
                        <?php endif; ?>
                        <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.75); color:#d4af37; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:800; backdrop-filter:blur(4px);">
                            #<?= esc((string)$rc['order_number']) ?>
                        </div>
                    </div>
                    <div style="padding:1rem; flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:8px;">
                        <div>
                            <div style="font-weight:700; color:#f4f4f5; font-size:0.9rem; margin-bottom:2px;">
                                👤 <?= esc((string)$rc['customer_name']) ?>
                            </div>
                            <div style="font-size:0.8rem; color:#a1a1aa; direction:ltr; text-align:right;">
                                📞 <?= esc((string)$rc['customer_phone']) ?>
                            </div>
                            <div style="font-size:0.88rem; font-weight:800; color:#10b981; margin-top:4px;">
                                💰 <?= number_format((float)$rc['total'], 2) ?> ج.م
                            </div>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #27272a; padding-top:8px; font-size:0.78rem;">
                            <span style="color:#71717a;"><?= date('Y-m-d H:i', strtotime($rc['created_at'])) ?></span>
                            <a href="<?= esc(admin_url('order_view.php?id=' . (int)$rc['id'])) ?>" target="_blank" style="color:#d4af37; font-weight:700; text-decoration:none;">عرض الطلب ↗</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

<!-- Receipt Modal -->
<div id="receiptModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center; padding:1.5rem;" onclick="closeReceiptModal()">
    <div style="background:#18181b; border:1px solid #27272a; border-radius:16px; max-width:600px; width:100%; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
        <div style="padding:1rem 1.25rem; border-bottom:1px solid #27272a; display:flex; justify-content:space-between; align-items:center;">
            <strong style="color:#f4f4f5;" id="modalOrderTitle">إيصال التحويل</strong>
            <button type="button" onclick="closeReceiptModal()" style="background:none; border:none; color:#a1a1aa; font-size:1.2rem; cursor:pointer;">✕</button>
        </div>
        <div style="padding:1rem; text-align:center; background:#09090b;">
            <img id="modalImg" src="" style="max-height:65vh; max-width:100%; object-fit:contain; border-radius:8px;">
        </div>
        <div style="padding:1rem 1.25rem; border-top:1px solid #27272a; display:flex; justify-content:space-between; align-items:center; font-size:0.88rem;">
            <span id="modalCustomerInfo" style="color:#a1a1aa;"></span>
            <a id="modalDownloadBtn" href="" target="_blank" download style="color:#d4af37; font-weight:700; text-decoration:none;">تحميل الصورة ⬇</a>
        </div>
    </div>
</div>

<script>
function openReceiptModal(url, orderNum, name, phone) {
    if (!url) return;
    document.getElementById('modalImg').src = url;
    document.getElementById('modalOrderTitle').innerText = 'إيصال طلب #' + orderNum;
    document.getElementById('modalCustomerInfo').innerText = 'العميل: ' + name + ' (' + phone + ')';
    document.getElementById('modalDownloadBtn').href = url;
    document.getElementById('receiptModal').style.display = 'flex';
}
function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

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
