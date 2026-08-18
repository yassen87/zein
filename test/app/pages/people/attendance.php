<?php
$users = all_users();
$locations = attendance_locations();
$userLocationId = current_user_location_id();
if ($userLocationId !== null) {
    $locations = array_values(array_filter($locations, fn ($l) => (int) $l['id'] === $userLocationId));
    $users = array_values(array_filter($users, fn ($u) => (int) $u['id'] === (int) $user['id']));
}
$tab = $_GET['tab'] ?? 'scan';

$db = pdo();
$stmt = $db->prepare("SELECT action FROM attendance_records WHERE user_id = ? AND DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$lastAction = $stmt->fetchColumn();
$canAction = ($lastAction === 'check_in') ? 'check_out' : 'check_in';

// Enforce logs visibility: regular cashiers can only view their own logs
$userIdFilter = has_permission('users_permissions') ? null : (int) $user['id'];
$rows = attendance_rows($userIdFilter);
?>
<section class="page-head">
    <div>
        <h2>سجل الحضور والانصراف الذكي</h2>
        <p>تسجيل الحضور والانصراف تلقائياً باستخدام كاميرا الهاتف للمسح أو يدوياً للمسؤولين.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a class="btn <?= $tab === 'scan' ? 'primary' : '' ?>" href="index.php?r=attendance&tab=scan">مسح QR بالكاميرا</a>
        <a class="btn <?= $tab === 'logs' ? 'primary' : '' ?>" href="index.php?r=attendance&tab=logs">سجل الحضور واليدوي</a>
        <?php if (has_permission('users_permissions')): ?>
            <a class="btn <?= $tab === 'qrcodes' ? 'primary' : '' ?>" href="index.php?r=attendance&tab=qrcodes">رموز QR للفروع</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($tab === 'qrcodes' && has_permission('users_permissions')): ?>
    <!-- Branch QR Codes List -->
    <div class="panel">
        <h3>رموز QR الخاصة بالفروع لتسجيل الحضور</h3>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--line); text-align: right;">
                    <th style="padding:10px;">الفرع / الموقع</th>
                    <th style="padding:10px;">النوع</th>
                    <th style="padding:10px; text-align:center;">رمز الـ QR</th>
                    <th style="padding:10px;">الموقع الجغرافي (GPS)</th>
                    <th style="padding:10px; text-align:center;">إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $l): ?>
                    <?php if ($l['type'] === 'online') continue; ?>
                    <tr style="border-bottom:1px solid var(--line);">
                        <td style="padding:10px; font-weight:700;"><?= e($l['name']) ?></td>
                        <td style="padding:10px;"><span class="badge"><?= e($l['type'] === 'warehouse' ? 'مخزن رئيسي' : 'فرع مبيعات') ?></span></td>
                        <td style="padding:10px; text-align:center;">
                            <?php if ($l['qr_code']): ?>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120&data=<?= urlencode($l['qr_code']) ?>" alt="QR" style="border: 1px solid var(--line); border-radius: 6px; padding: 4px; background: #fff; width:80px; height:80px;">
                            <?php else: ?>
                                <span class="muted">لا يوجد رمز حالياً</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px;">
                            <form method="post" class="inline" style="display:flex; gap:6px; align-items:center; margin:0;">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_location_geo">
                                <input type="hidden" name="location_id" value="<?= e($l['id']) ?>">
                                <input type="number" step="0.0000001" name="latitude" value="<?= e($l['latitude'] ?? '') ?>" placeholder="Latitude" style="width:100px; padding:4px;" required>
                                <input type="number" step="0.0000001" name="longitude" value="<?= e($l['longitude'] ?? '') ?>" placeholder="Longitude" style="width:100px; padding:4px;" required>
                                <button type="button" class="btn small" onclick="getCurrentLocationForBranch(this)">📍 موقعي</button>
                                <button class="btn small primary">حفظ</button>
                            </form>
                        </td>
                        <td style="padding:10px; text-align:center;">
                            <form method="post" style="display:inline-block; margin:0 4px;">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="generate_qr">
                                <input type="hidden" name="location_id" value="<?= e($l['id']) ?>">
                                <button class="btn small"><?= $l['qr_code'] ? 'إعادة توليد الرمز' : 'توليد رمز QR' ?></button>
                            </form>
                            <?php if ($l['qr_code']): ?>
                                <button type="button" class="btn small success" onclick="printQRCode('<?= e(addslashes($l['name'])) ?>', '<?= e(addslashes($l['qr_code'])) ?>')">طباعة الرمز 🖨️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function printQRCode(name, qrCode) {
        const url = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrCode)}`;
        const win = window.open('', '_blank');
        win.document.write(`
            <html>
            <head>
                <title>طباعة QR - ${name}</title>
                <style>
                    body { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; font-family:'Cairo', Arial, sans-serif; margin:0; text-align:center; }
                    img { width: 320px; height: 320px; margin-bottom: 25px; border: 2px solid #000; padding:10px; border-radius: 12px; }
                    h1 { font-size: 26px; margin: 0; color: #111; }
                    p { font-size: 14px; color: #666; margin-top: 5px; }
                </style>
            </head>
            <body onload="setTimeout(() => { window.print(); window.close(); }, 500);">
                <img src="${url}">
                <h1>بوابة الحضور والانصراف الذكي</h1>
                <h1>الفرع: ${name}</h1>
                <p>قم بمسح هذا الرمز عبر هاتفك لتسجيل الحضور أو الانصراف</p>
            </body>
            </html>
        `);
        win.document.close();
    }

    function getCurrentLocationForBranch(btn) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                const form = btn.closest('form');
                form.querySelector('input[name="latitude"]').value = pos.coords.latitude.toFixed(7);
                form.querySelector('input[name="longitude"]').value = pos.coords.longitude.toFixed(7);
            }, (err) => {
                alert("خطأ في تحديد الموقع الجغرافي: " + err.message);
            });
        } else {
            alert("متصفحك لا يدعم تحديد الموقع الجغرافي.");
        }
    }
    </script>

<?php elseif ($tab === 'logs'): ?>
    <!-- Manual Register & Logs -->
    <?php if (has_permission('users_permissions')): ?>
        <form class="panel grid-form" method="post" data-geo-form>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>الموظف<select name="user_id"><?php foreach ($users as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select></label>
            <label>الفرع<select name="location_id"><?php foreach ($locations as $l): ?><option value="<?= e($l['id']) ?>"><?= e($l['name']) ?></option><?php endforeach; ?></select></label>
            <label>العملية<select name="action"><option value="check_in">حضور</option><option value="check_out">انصراف</option></select></label>
            <label>المصدر<select name="source"><option value="manual">يدوي</option></select></label>
            <label>Latitude<input name="latitude" type="number" step="0.0000001" data-lat></label>
            <label>Longitude<input name="longitude" type="number" step="0.0000001" data-lng></label>
            <label style="grid-column: span 2;">ملاحظة<input name="notes"></label>
            <div style="display: flex; gap: 6px; margin-top: 10px;">
                <button class="btn" type="button" data-get-location>تحديد موقعي</button>
                <button class="btn primary">تسجيل يدوي</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="panel">
        <h3>سجلات الحضور والانصراف</h3>
        <table>
            <thead>
                <tr>
                    <th>التاريخ والوقت</th>
                    <th>الموظف</th>
                    <th>الموقع</th>
                    <th>العملية</th>
                    <th>مدة العمل الفعلي</th>
                    <th>المصدر</th>
                    <th>الموقع الجغرافي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $a): ?>
                    <tr>
                        <td><?= e($a['created_at']) ?></td>
                        <td><strong><?= e($a['user_name']) ?></strong></td>
                        <td><?= e($a['location_name']) ?></td>
                        <td>
                            <span class="badge <?= $a['action'] === 'check_in' ? 'success' : 'warning' ?>">
                                <?= $a['action'] === 'check_in' ? 'حضور' : 'انصراف' ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $durationText = '-';
                            if ($a['action'] === 'check_out' && !empty($a['matching_check_in'])) {
                                $in = new DateTime($a['matching_check_in']);
                                $out = new DateTime($a['created_at']);
                                $diff = $in->diff($out);
                                $hours = $diff->h + ($diff->days * 24);
                                $minutes = $diff->i;
                                $durationText = "";
                                if ($hours > 0) {
                                    $durationText .= $hours . " ساعة ";
                                }
                                if ($minutes > 0 || $hours === 0) {
                                    $durationText .= $minutes . " دقيقة";
                                }
                            }
                            echo e($durationText);
                            ?>
                        </td>
                        <td><span class="chip"><?= e($a['source'] === 'qr' ? 'QR' : 'يدوي') ?></span></td>
                        <td>
                            <?php if ($a['latitude'] && $a['longitude']): ?>
                                <a href="https://maps.google.com/?q=<?= e($a['latitude']) ?>,<?= e($a['longitude']) ?>" target="_blank" style="color:var(--primary); font-weight:700;">عرض على الخريطة 📍</a>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <!-- QR Scanning Camera Tab -->
    <div class="panel" style="text-align: center; max-width: 500px; margin: 0 auto;">
        <h3>مسح الـ QR لتسجيل حضور/انصراف</h3>
        <p class="muted" style="margin-bottom: 20px;">افتح الكاميرا وقم بمسح رمز الـ QR المعلق بالفرع أو المخزن.</p>
        
        <div style="margin-bottom: 20px;">
            <label style="display:inline-block; font-weight:bold; margin-bottom:8px;">العملية الحالية:</label><br>
            <div style="display:inline-flex; gap: 20px; font-size:16px;">
                <?php if ($canAction === 'check_in'): ?>
                    <span class="badge success" style="font-size: 16px; padding: 6px 16px;">تسجيل حضور 🟢</span>
                    <input type="hidden" id="scan_action_select_val" value="check_in">
                <?php else: ?>
                    <span class="badge warning" style="font-size: 16px; padding: 6px 16px;">تسجيل انصراف 🔴</span>
                    <input type="hidden" id="scan_action_select_val" value="check_out">
                <?php endif; ?>
            </div>
        </div>
        
        <button type="button" class="btn primary" id="btn-start-scanner" style="padding: 10px 24px; font-size:14px; margin-bottom: 15px;">📷 فتح كاميرا الهاتف للمسح</button>
        
        <div id="scanner-container" style="display:none; margin: 20px 0;">
            <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto; border: 2px solid var(--line); border-radius: 12px; overflow: hidden; background:#000;"></div>
            <button type="button" class="btn danger small" id="btn-stop-scanner" style="margin-top:10px;">إغلاق الكاميرا</button>
        </div>

        <form id="qr-scan-form" method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="qr_scan">
            <input type="hidden" name="qr_token" id="qr-token-input">
            <input type="hidden" name="scan_action" id="scan-action-input">
            <input type="hidden" name="latitude" id="lat-input">
            <input type="hidden" name="longitude" id="lng-input">
        </form>
    </div>

    <!-- Load html5-qrcode library locally for offline access -->
    <script src="assets/html5-qrcode.min.js"></script>
    <script>
    let html5QrcodeScanner = null;
    
    document.getElementById('btn-start-scanner')?.addEventListener('click', () => {
        const container = document.getElementById('scanner-container');
        const startBtn = document.getElementById('btn-start-scanner');
        
        // Check for secure context (HTTPS or localhost)
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            alert("تنبيه هام: المتصفحات تحظر استخدام الكاميرا عبر بروتوكول HTTP غير الآمن. لتشغيل الكاميرا من الهاتف، يرجى تفعيل HTTPS على السيرفر، أو تجربة مسح الـ QR من الكمبيوتر نفسه عبر localhost.");
        }
        
        container.style.display = 'block';
        startBtn.style.display = 'none';
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                document.getElementById('lat-input').value = pos.coords.latitude.toFixed(7);
                document.getElementById('lng-input').value = pos.coords.longitude.toFixed(7);
            }, () => {
                console.log("Geolocation error or permission denied.");
            });
        }

        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            alert("خطأ في تشغيل الكاميرا: " + err);
            stopScanner();
        });
    });

    document.getElementById('btn-stop-scanner')?.addEventListener('click', () => {
        stopScanner();
    });

    function stopScanner() {
        const container = document.getElementById('scanner-container');
        const startBtn = document.getElementById('btn-start-scanner');
        if (container) container.style.display = 'none';
        if (startBtn) startBtn.style.display = 'inline-block';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner = null;
            }).catch(err => {
                console.error("Failed to stop scanner", err);
            });
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        stopScanner();
        
        document.getElementById('qr-token-input').value = decodedText;
        
        const selectedAction = document.getElementById('scan_action_select_val').value;
        document.getElementById('scan-action-input').value = selectedAction;
        document.getElementById('qr-scan-form').submit();
    }

    function onScanFailure(error) {
        // Silent
    }
    </script>
<?php endif; ?>
