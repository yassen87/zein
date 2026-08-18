<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = t('admin_settings');

$pdo = medal_pdo();
$success = false;
$error   = '';

// ── Auto-migrate: add image column to categories if missing ─────────────────
if ($pdo && !isset($_SESSION['_migrated_categories_image'])) {
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS image VARCHAR(500) DEFAULT ''"); }
    catch (Throwable) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetchAll();
            if (empty($cols)) $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) DEFAULT ''");
        } catch (Throwable) {}
    }
    $_SESSION['_migrated_categories_image'] = true;
}

$uploadDir = dirname(__DIR__) . '/assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// ── POST handler ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_verify_csrf();

        if ($pdo !== null) {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                "INSERT INTO settings (setting_key, setting_value_en, setting_value_ar)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value_en = VALUES(setting_value_en), setting_value_ar = VALUES(setting_value_ar)"
            );

            // Announcement
            $st->execute(['announce_shipping', $_POST['announce_shipping_en'] ?? '', $_POST['announce_shipping_ar'] ?? '']);

            // Women Category Message
            $st->execute(['women_category_cart_message', $_POST['women_category_cart_message_en'] ?? '', $_POST['women_category_cart_message_ar'] ?? '']);

            // GA & FB Pixel
            $st->execute(['ga_id', $_POST['ga_id'] ?? '', $_POST['ga_id'] ?? '']);
            $st->execute(['fb_pixel_id', $_POST['fb_pixel_id'] ?? '', $_POST['fb_pixel_id'] ?? '']);

            // Social Login - Google only
            $socialKeys = ['google_client_id', 'google_client_secret'];
            foreach ($socialKeys as $sk) {
                $val = trim((string)($_POST[$sk] ?? ''));
                $st->execute([$sk, $val, $val]);
            }

            // Hero images (uploaded files OR existing keys kept OR cleared)
            for ($i = 1; $i <= 3; $i++) {
                $key = 'hero_image_' . $i;
                $existing = trim((string)($_POST[$key . '_existing'] ?? ''));

                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES[$key]['size'] > 1 * 1024 * 1024) {
                        throw new RuntimeException("صورة #$i: حجم الملف كبير جداً. الحد الأقصى 1 ميجابايت / 1MB.");
                    }
                    $tmp  = $_FILES[$key]['tmp_name'];
                    $orig = basename($_FILES[$key]['name']);
                    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                        throw new RuntimeException("صورة #$i: صيغة الملف غير مدعومة.");
                    }
                    $filename = 'hero_' . $i . '_' . time() . '.' . $ext;
                    move_uploaded_file($tmp, $uploadDir . $filename);
                    $st->execute([$key, $filename, $filename]);
                } elseif ($existing !== '') {
                    // keep existing value (no new upload)
                    $st->execute([$key, $existing, $existing]);
                } else {
                    // image has been deleted / cleared
                    $st->execute([$key, '', '']);
                }
            }

            // Hero links
            for ($i = 1; $i <= 3; $i++) {
                $linkKey = 'hero_link_' . $i;
                $linkVal = trim((string)($_POST[$linkKey] ?? ''));
                $st->execute([$linkKey, $linkVal, $linkVal]);
            }

            // Hero text
            $heroTextKeys = ['hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_link'];
            foreach ($heroTextKeys as $hk) {
                $en = trim((string)($_POST[$hk . '_en'] ?? ''));
                $ar = trim((string)($_POST[$hk . '_ar'] ?? ''));
                $st->execute([$hk, $en, $ar]);
            }

            // SMTP Email Settings
            $smtpKeys = ['smtp_email', 'smtp_password', 'smtp_from_name', 'smtp_host', 'smtp_port'];
            foreach ($smtpKeys as $sk) {
                $val = trim((string)($_POST[$sk] ?? ''));
                $st->execute([$sk, $val, $val]);
            }

            // Payment & WhatsApp Bot Settings
            $payKeys = ['instapay_username', 'vodafone_cash_number', 'bank_account_info', 'whatsapp_bot_url', 'whatsapp_bot_phone'];
            foreach ($payKeys as $pk) {
                $valAr = trim((string)($_POST[$pk . '_ar'] ?? $_POST[$pk] ?? ''));
                $valEn = trim((string)($_POST[$pk . '_en'] ?? $_POST[$pk] ?? ''));
                $st->execute([$pk, $valEn, $valAr]);
            }

            $pdo->commit();
            $success = true;
        }
    } catch (Throwable $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// ── Load current settings ────────────────────────────────────────────────────
$settings_data = ['announce_shipping' => ['en' => '', 'ar' => ''], 'women_category_cart_message' => ['en' => '', 'ar' => ''], 'ga_id' => ['en' => '', 'ar' => ''], 'fb_pixel_id' => ['en' => '', 'ar' => '']];
$heroImages = ['hero_image_1' => '', 'hero_image_2' => '', 'hero_image_3' => ''];
$heroLinks = ['hero_link_1' => '', 'hero_link_2' => '', 'hero_link_3' => ''];
$heroText = ['hero_title' => ['en' => '', 'ar' => ''], 'hero_subtitle' => ['en' => '', 'ar' => ''], 'hero_cta_text' => ['en' => '', 'ar' => ''], 'hero_cta_link' => ['en' => '', 'ar' => '']];
$settings_data = array_merge($settings_data, [
    'google_client_id'    => ['en' => '', 'ar' => ''],
    'google_client_secret'=> ['en' => '', 'ar' => ''],
    'smtp_email'          => ['en' => '', 'ar' => ''],
    'smtp_password'       => ['en' => '', 'ar' => ''],
    'smtp_from_name'      => ['en' => '', 'ar' => ''],
    'smtp_host'           => ['en' => '', 'ar' => ''],
    'smtp_port'           => ['en' => '', 'ar' => ''],
    'instapay_username'   => ['en' => '', 'ar' => ''],
    'vodafone_cash_number'=> ['en' => '', 'ar' => ''],
    'bank_account_info'   => ['en' => '', 'ar' => ''],
    'whatsapp_bot_url'    => ['en' => '', 'ar' => ''],
    'whatsapp_bot_phone'  => ['en' => '', 'ar' => ''],
]);

if ($pdo) {
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value_en, setting_value_ar FROM settings")->fetchAll();
        foreach ($rows as $r) {
            $k = $r['setting_key'];
            if (isset($settings_data[$k])) {
                $settings_data[$k]['en'] = $r['setting_value_en'];
                $settings_data[$k]['ar'] = $r['setting_value_ar'];
            }
            if (isset($heroImages[$k])) {
                $heroImages[$k] = $r['setting_value_en'];
            }
            if (isset($heroLinks[$k])) {
                $heroLinks[$k] = $r['setting_value_en'];
            }
            if (isset($heroText[$k])) {
                $heroText[$k]['en'] = $r['setting_value_en'];
                $heroText[$k]['ar'] = $r['setting_value_ar'];
            }
        }
    } catch (Throwable) {}
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1><?= esc(t('admin_settings')) ?></h1>
        <p class="admin-lead" style="margin-bottom:0">ضبط إعدادات الموقع وصور الصفحة الرئيسية.</p>
    </div>
</div>

<?php if ($success): ?>
<div style="background:#d4edda;color:#155724;padding:1rem 1.5rem;border-radius:10px;border:1px solid #c3e6cb;margin-bottom:1.5rem;font-weight:600;">
    ✅ تم حفظ الإعدادات بنجاح!
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="admin-error" style="margin-bottom:1.5rem;"><?= esc($error) ?></div>
<?php endif; ?>

<!-- ═══ Database Management Quick Access Card ═════════════════════════ -->
<div class="admin-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1.5px solid rgba(212, 175, 55, 0.4); border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 2rem; color: #ffffff; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
    <div>
        <div style="font-size: 0.8rem; font-weight: 800; color: #f59e0b; margin-bottom: 0.25rem;">
            👑 DATABASE STUDIO & BACKUPS
        </div>
        <h2 style="margin: 0 0 0.35rem 0; font-size: 1.3rem; font-weight: 900; color: #ffffff;">
            🗄️ إدارة قاعدة البيانات واستيراد ملفات SQL والنسخ الاحتياطي
        </h2>
        <p style="margin: 0; font-size: 0.85rem; color: #94a3b8;">
            رفع ملفات قاعدة البيانات (.sql)، تصدير النسخ الاحتياطية، وحذف البيانات التجريبية بضغطة زر.
        </p>
    </div>
    <div>
        <a href="<?= esc(admin_url('database_manage.php')) ?>" style="background: linear-gradient(135deg, #d4af37 0%, #b45309 100%); color: #ffffff; padding: 0.85rem 1.75rem; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.35);">
            <span>🚀</span> فتح استوديو قاعدة البيانات
        </a>
    </div>
</div>

<form class="admin-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">

    <!-- ═══ Hero Text ════════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:0.5rem;">📝 نصوص السلايدر (الصورة الأولى)</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.5rem;">النص الذي يظهر فوق الصورة الأولى في السلايدر.</p>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">العنوان الرئيسي (إنجليزي)</label>
                <input type="text" name="hero_title_en" value="<?= esc($heroText['hero_title']['en']) ?>" placeholder="Discover Luxury Fragrances"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">العنوان الرئيسي (عربي)</label>
                <input type="text" name="hero_title_ar" dir="rtl" value="<?= esc($heroText['hero_title']['ar']) ?>" placeholder="اكتشف أفخر العطور"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">النص الفرعي (إنجليزي)</label>
                <input type="text" name="hero_subtitle_en" value="<?= esc($heroText['hero_subtitle']['en']) ?>" placeholder="Premium Arabic & French Perfumes"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">النص الفرعي (عربي)</label>
                <input type="text" name="hero_subtitle_ar" dir="rtl" value="<?= esc($heroText['hero_subtitle']['ar']) ?>" placeholder="عطور عربية وفرنسية فاخرة"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">نص الزر (إنجليزي)</label>
                <input type="text" name="hero_cta_text_en" value="<?= esc($heroText['hero_cta_text']['en']) ?>" placeholder="Shop Now"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">نص الزر (عربي)</label>
                <input type="text" name="hero_cta_text_ar" dir="rtl" value="<?= esc($heroText['hero_cta_text']['ar']) ?>" placeholder="تسوق الآن"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
        
        <div>
            <label style="display:block; margin-bottom:.4rem; font-weight:600; font-size:.88rem;">رابط الزر</label>
            <input type="text" name="hero_cta_link_en" value="<?= esc($heroText['hero_cta_link']['en']) ?>" placeholder="products.php"
                   style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; max-width:400px;">
            <input type="hidden" name="hero_cta_link_ar" value="<?= esc($heroText['hero_cta_link']['ar'] ?: $heroText['hero_cta_link']['en']) ?>">
        </div>
    </div>

    <!-- ═══ Hero Images ════════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:0.5rem;">🖼️ صور الصفحة الرئيسية (السلايدر)</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.5rem;">ارفع حتى 3 صور للسلايدر. الصور المقترحة: 1200×500px أو أوسع. صيغ مدعومة: JPG، PNG، WebP.</p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.25rem;">
            <?php for ($i = 1; $i <= 3; $i++):
                $hkey = 'hero_image_' . $i;
                $hval = $heroImages[$hkey];
                $hurl = $hval !== '' ? base_url('assets/uploads/' . $hval) : '';
            ?>
            <div>
                <label style="font-weight:600; font-size:0.88rem; display:block; margin-bottom:0.6rem;">صورة <?= $i ?></label>

                <!-- Preview -->
                <div class="hero-preview-box" id="preview_hero_<?= $i ?>"
                     style="width:100%; aspect-ratio:2.4/1; border-radius:10px; border:2px dashed #ddd; background:#f9f9f9; overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:0.6rem; cursor:pointer; position:relative;"
                     onclick="document.getElementById('file_<?= $hkey ?>').click()">
                    <?php if ($hurl !== ''): ?>
                        <img src="<?= esc($hurl) ?>" style="width:100%; height:100%; object-fit:cover;" id="img_hero_<?= $i ?>">
                    <?php else: ?>
                        <div id="img_hero_<?= $i ?>_placeholder" style="text-align:center; color:#bbb;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            <p style="margin:.4rem 0 0; font-size:.75rem;">اضغط لرفع صورة</p>
                        </div>
                    <?php endif; ?>
                    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.3); opacity:0; transition:.2s; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.8rem; border-radius:8px;" class="hero-preview-overlay">
                        تغيير الصورة
                    </div>
                </div>

                <input type="file" id="file_<?= $hkey ?>" name="<?= $hkey ?>"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       style="display:none"
                       onchange="previewHero(this, <?= $i ?>)">
                <input type="hidden" name="<?= $hkey ?>_existing" value="<?= esc($hval) ?>">

                <?php if ($hval !== ''): ?>
                <div style="font-size:0.75rem; color:var(--admin-text-muted); text-align:center; margin-top:.3rem;">
                    📎 <?= esc($hval) ?>
                    <button type="button" onclick="clearHero(<?= $i ?>,'<?= esc(admin_url('settings.php')) ?>')"
                            style="background:none;border:none;color:#e53e3e;cursor:pointer;font-size:.75rem;padding:0 .3rem;">✕ حذف</button>
                </div>
                <?php endif; ?>
                <label style="font-weight:600; font-size:0.8rem; display:block; margin-top:0.5rem; margin-bottom:0.3rem;">رابط الصورة <?= $i ?></label>
                <input type="text" name="hero_link_<?= $i ?>" 
                       value="<?= esc($heroLinks['hero_link_' . $i] ?? '') ?>"
                       placeholder="مثال: products.php?cat=..."
                       style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.8rem; box-sizing:border-box;">
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ═══ Announcement ═══════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">📢 إعلان شريط الشحن</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">الإعلان (إنجليزي)</label>
                <input type="text" name="announce_shipping_en"
                       value="<?= esc($settings_data['announce_shipping']['en']) ?>"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">الإعلان (عربي)</label>
                <input type="text" name="announce_shipping_ar" dir="rtl"
                       value="<?= esc($settings_data['announce_shipping']['ar']) ?>"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
    </div>

    <!-- ═══ Women Category Message ═══════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">👗 رسالة قسم النساء</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.25rem;">الرسالة التي تظهر على الشاشة عند إضافة منتج من قسم النساء إلى السلة.</p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">الرسالة (إنجليزي)</label>
                <textarea name="women_category_cart_message_en"
                          rows="3"
                          style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:inherit;"><?= esc($settings_data['women_category_cart_message']['en']) ?></textarea>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">الرسالة (عربي)</label>
                <textarea name="women_category_cart_message_ar" dir="rtl"
                          rows="3"
                          style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:inherit;"><?= esc($settings_data['women_category_cart_message']['ar']) ?></textarea>
            </div>
        </div>
    </div>

    <!-- ═══ Tracking Codes ═══════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">📊 أكواد التتبع والتحليلات</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">Google Analytics ID</label>
                <input type="text" name="ga_id"
                       value="<?= esc($settings_data['ga_id']['en']) ?>"
                       placeholder="G-XXXXXXXXXX"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:0.35rem;">أدخل معرف Google Analytics (مثال: G-XXXXXXXXXX)</p>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">Facebook Pixel ID</label>
                <input type="text" name="fb_pixel_id"
                       value="<?= esc($settings_data['fb_pixel_id']['en']) ?>"
                       placeholder="1234567890123456"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:0.35rem;">أدخل معرف Facebook Pixel</p>
            </div>
        </div>
    </div>

    <!-- ═══ Social Login ═══════════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">🔑 تسجيل الدخول الاجتماعي</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">Google Client ID</label>
                <input type="text" name="google_client_id" value="<?= esc($settings_data['google_client_id']['en'] ?? '') ?>"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">Google Client Secret</label>
                <input type="text" name="google_client_secret" value="<?= esc($settings_data['google_client_secret']['en'] ?? '') ?>"
                       style="width:100%; padding:.7rem; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
        <p style="color:var(--admin-text-muted); font-size:0.8rem; margin-top:1rem;">
            رابط إعادة التوجيه لـ Google: <code style="background:#f5f5f5;padding:2px 6px;border-radius:4px;"><?= esc(base_url('client/auth/google.php')) ?></code>
        </p>
    </div>

    <!-- ═══ SMTP Email Settings ════════════════════════════════════════════ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:0.5rem;">📧 إعدادات إيميل الإرسال (SMTP)</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.5rem;">
            الإيميل وكلمة المرور اللي بيبعت منه تأكيد الطلبات ورموز التحقق.<br>
            <strong>Gmail:</strong> استخدم <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#c5a059;">App Password</a> مش كلمة المرور الأصلية.
        </p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">📮 إيميل المرسل</label>
                <input type="email" name="smtp_email"
                       value="<?= esc($settings_data['smtp_email']['en']) ?>"
                       placeholder="yourstore@gmail.com"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:0.35rem;">الإيميل اللي بيظهر كمرسل للعميل</p>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🔑 App Password</label>
                <input type="text" name="smtp_password"
                       value="<?= esc($settings_data['smtp_password']['en']) ?>"
                       placeholder="xxxx xxxx xxxx xxxx"
                       autocomplete="off"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace;">
                <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:0.35rem;">من Google → حسابي → الأمان → App Passwords</p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">✏️ اسم المرسل</label>
                <input type="text" name="smtp_from_name"
                       value="<?= esc($settings_data['smtp_from_name']['en']) ?>"
                       placeholder="Zain Perfumes"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🖥️ SMTP Host</label>
                <input type="text" name="smtp_host"
                       value="<?= esc($settings_data['smtp_host']['en']) ?>"
                       placeholder="smtp.gmail.com"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🔌 Port</label>
                <input type="number" name="smtp_port"
                       value="<?= esc($settings_data['smtp_port']['en'] ?: '587') ?>"
                       placeholder="587"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
        </div>

        <!-- When are emails sent info box -->
        <div style="margin-top:1.5rem; background:#f8f9fa; border-radius:10px; padding:1rem 1.25rem; border:1px solid #e9ecef;">
            <p style="margin:0 0 0.5rem; font-weight:700; font-size:0.88rem; color:#333;">📋 متى يبعت السيستم إيميلات؟</p>
            <ul style="margin:0; padding-right:1.25rem; font-size:0.83rem; color:#555; line-height:1.8;">
                <li>✅ <strong>تأكيد الطلب</strong> — فور إتمام الشراء من الـ checkout</li>
                <li>🔐 <strong>رمز OTP للتسجيل</strong> — عند إنشاء حساب جديد (client/verify.php)</li>
                <li>🔑 <strong>رمز استعادة كلمة المرور</strong> — عند طلب reset password</li>
                <li>📦 <strong>إعادة إرسال تأكيد الطلب</strong> — يدوياً من الأدمن (صفحة تفاصيل الطلب)</li>
            </ul>
        </div>
    </div>

    <!-- ── Payment & WhatsApp Bot Settings ── -->
    <div class="admin-card" style="margin-bottom:2rem; padding:1.5rem 2rem;">
        <h2 style="font-size:1.2rem; font-weight:700; margin-bottom:.5rem;">💳 إعدادات الدفع (انستاباي ومحافظ) وبوت الواتساب</h2>
        <p style="color:#666; font-size:.88rem; margin-bottom:1.5rem;">
            تحديد بيانات التحويل التي تظهر للعميل في شات الواتساب وصفحة تأكيد الطلب لاستلام صور إيصالات التحويل.
        </p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🟣 عنوان أو رابط انستاباي (InstaPay IPA / Link)</label>
                <input type="text" name="instapay_username"
                       value="<?= esc($settings_data['instapay_username']['en'] ?: $settings_data['instapay_username']['ar']) ?>"
                       placeholder="zain@instapay"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                <span style="font-size:0.78rem; color:#888; display:block; margin-top:4px;">مثال: username@instapay أو رقم التليفون المسجل</span>
            </div>

            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🔴 رقم محفظة فودافون كاش / أورانج / اتصالات</label>
                <input type="text" name="vodafone_cash_number"
                       value="<?= esc($settings_data['vodafone_cash_number']['en'] ?: $settings_data['vodafone_cash_number']['ar']) ?>"
                       placeholder="01111026600"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                <span style="font-size:0.78rem; color:#888; display:block; margin-top:4px;">الرقم المخصص لاستلام التحويلات الإلكترونية</span>
            </div>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🏦 بيانات الحساب البنكي / الآيبان (اختياري)</label>
            <input type="text" name="bank_account_info_ar"
                   value="<?= esc($settings_data['bank_account_info']['ar'] ?: $settings_data['bank_account_info']['en']) ?>"
                   placeholder="البنك الأهلي المصري - حساب رقم 123456789 - آيبان EG123..."
                   style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🤖 رابط سيرفر خدمة بوت الواتساب (Microservice URL)</label>
                <input type="text" name="whatsapp_bot_url"
                       value="<?= esc($settings_data['whatsapp_bot_url']['en'] ?: 'https://wa.zeinperfumes.com') ?>"
                       placeholder="https://wa.zeinperfumes.com"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace;">
            </div>

            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">📲 رقم هاتف المتجر الأساسي للتواصل</label>
                <input type="text" name="whatsapp_bot_phone"
                       value="<?= esc($settings_data['whatsapp_bot_phone']['en'] ?: '201111026600') ?>"
                       placeholder="201111026600"
                       style="width:100%; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
            </div>
        </div>
    </div>

    <div style="text-align:left;">
        <button type="submit" class="btn-admin" style="background:#111;color:#fff;padding:.9rem 3rem;border:none;border-radius:50px;font-weight:700;font-size:.95rem;cursor:pointer;">
            💾 حفظ الإعدادات
        </button>
    </div>
</form>

<style>
.hero-preview-box:hover .hero-preview-overlay { opacity:1 !important; }
@media(max-width:768px){
    form .admin-card div[style*="grid-template-columns:repeat(3"]{grid-template-columns:1fr !important;}
    form .admin-card div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr !important;}
}
</style>
<script>
function previewHero(input, idx) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const box = document.getElementById('preview_hero_' + idx);
        // Replace placeholder with img or update existing img
        let img = box.querySelector('img');
        if (!img) {
            box.innerHTML = '';
            img = document.createElement('img');
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            box.appendChild(img);
        }
        img.src = e.target.result;
        // Remove placeholder
        const ph = document.getElementById('img_hero_' + idx + '_placeholder');
        if (ph) ph.remove();
    };
    reader.readAsDataURL(input.files[0]);
}
function clearHero(idx, settingsUrl) {
    if (!confirm('هل تريد حذف صورة ' + idx + '؟')) return;
    // Set existing to empty so it won't be re-saved
    const inputs = document.querySelectorAll('input[name="hero_image_' + idx + '_existing"]');
    inputs.forEach(i => i.value = '');
    const box = document.getElementById('preview_hero_' + idx);
    if (box) {
        box.innerHTML = '<div style="text-align:center;color:#bbb;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg><p style="margin:.4rem 0 0;font-size:.75rem;">اضغط لرفع صورة</p></div><div style="position:absolute;inset:0;background:rgba(0,0,0,0.3);opacity:0;transition:.2s;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;border-radius:8px;" class="hero-preview-overlay">تغيير الصورة</div>';
    }
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
