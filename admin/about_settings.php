<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إعدادات صفحة من نحن';

$pdo = medal_pdo();
$success = false;
$error   = '';

$fields = [
    'about_hero_title',
    'about_hero_lead',
    'about_story_title',
    'about_story_image',
    'about_story_p1',
    'about_story_p2',
    'about_story_p3',
    'about_values_title',
    'about_values_lead',
    'about_values_en',
    'about_values_ar',
    'about_promises_en',
    'about_promises_ar',
    'about_promise_title',
    'about_promise_text',
];

$defaults = [
    'about_hero_title'   => ['en' => 'Fragrances That <em>Tell a Story</em>',        'ar' => 'عطورٌ <em>تحكي قصة</em>'],
    'about_hero_lead'    => ['en' => "Since our founding, we believe fragrance isn't just a scent — it's a memory in the making, a personality expressed.", 'ar' => 'منذ تأسيسنا، ونحن نؤمن بأن العطر ليس مجرد رائحة — بل هو ذكرى تُصنع، وشخصية تُعبّر.'],
    'about_story_title'  => ['en' => 'From a Small Passion to a <em>Known Fragrance House</em>', 'ar' => 'من شغفٍ صغير إلى <em>دار عطور</em> معروفة'],
    'about_story_image'  => ['en' => '', 'ar' => ''],
    'about_story_p1'     => ['en' => "Zain Perfumes started from a genuine passion for the world of fragrance and the art of composition, with the belief that every person has a scent that expresses their identity and leaves a beautiful impression wherever they go.", 'ar' => 'بدأت زين للعطور من شغف حقيقي بعالم العطور وفن التركيب، إيمانًا بأن لكل شخص عطره الذي يعبّر عن هويته ويترك أثرًا جميلًا في كل مكان يمر به.'],
    'about_story_p2'     => ['en' => "Today we offer over 500 original fragrances at competitive prices, from authentic Gulf perfumes and luxury oud to global signatures and exclusive scents. Every piece carefully selected to suit different tastes.", 'ar' => 'اليوم نقدم أكثر من 500 عطر أصيل بأسعار تنافسية، بدءًا من العطور الخليجية الأصيلة والعود الفاخر، وصولًا إلى التوقيعات العالمية والعطور الحصرية. كل قطعة مختارة بعناية لتناسب أذواق مختلفة.'],
    'about_story_p3'     => ['en' => "We believe quality fragrance shouldn't be limited to a high price tag, which is why we always strive to deliver the best experience at fair prices with service from the heart.", 'ar' => 'نؤمن بأن جودة العطر لا يجب أن تكون حكرًا على سعر باهظ، لذلك نسعى دائمًا لتقديم أفضل تجربة بأسعار عادلة وخدمة من القلب.'],
    'about_values_title' => ['en' => 'Our Values <em>&amp; Philosophy</em>', 'ar' => 'قيمنا <em>وفلسفتنا</em>'],
    'about_values_lead'  => ['en' => 'The principles that guide every decision we make.', 'ar' => 'المبادئ التي تقود كل قرار نتخذه.'],
    'about_values_en'    => ['en' => "💎|Quality First|We carefully select every fragrance and guarantee the authenticity of every product.\n🌟|Unique Experience|We strive to make every interaction with Zain Perfumes a delightful experience.\n🤝|Service from the Heart|Our team is always on WhatsApp to give you the best recommendation.\n🚀|Fast & Safe Shipping|Professional packaging ensures your order arrives safely and quickly.\n💰|Competitive Prices|Luxury accessible to everyone — without compromising on quality.\n🔄|Flexible Policy|A fair and clear exchange policy to ensure your full peace of mind.", 'ar' => ''],
    'about_values_ar'    => ['ar' => "💎|الجودة أولاً|نختار كل عطر بعناية ونضمن أصالة كل منتج نقدمه.\n🌟|تجربة فريدة|نسعى لأن يكون كل تفاعل مع زين للعطور تجربة ممتعة.\n🤝|خدمة من القلب|فريقنا دائمًا متاح عبر واتساب لتقديم أفضل توصية.\n🚀|شحن سريع وآمن|تغليف احترافي يضمن وصول طلبك بأمان وسرعة.\n💰|أسعار تنافسية|الفخامة في متناول الجميع — بلا مساومة على الجودة.\n🔄|سياسة مرنة|سياسة استبدال عادلة وواضحة لراحتك الكاملة.", 'en' => ''],
    'about_promises_en'  => ['en' => "Over 500 authentic & diverse fragrances\n100% product authenticity guarantee\nFast shipping within 24–72 hours\nWhatsApp support all week long\nFair and clear exchange policy\nProfessional packaging to protect your order", 'ar' => ''],
    'about_promises_ar'  => ['ar' => "أكثر من ٥٠٠ عطر أصيل ومتنوع\nضمان أصالة المنتجات ١٠٠٪\nشحن سريع خلال ٢٤–٧٢ ساعة\nدعم واتساب طوال أيام الأسبوع\nسياسة استبدال عادلة وواضحة\nتغليف احترافي يحافظ على العطر", 'en' => ''],
    'about_promise_title'=> ['en' => 'An <em>Unforgettable</em> Shopping Experience', 'ar' => 'تجربة شراء <em>لا تُنسى</em>'],
    'about_promise_text' => ['en' => "Every order is an opportunity to prove our commitment to quality and service. Shop with confidence — we're with you every step.", 'ar' => 'كل طلب هو فرصة لنثبت التزامنا بالجودة وخدمة العملاء. تسوّق بثقة — نحن معك في كل خطوة.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    try {
        admin_verify_csrf();

        $st = $pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value_en, setting_value_ar)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value_en = VALUES(setting_value_en), setting_value_ar = VALUES(setting_value_ar)"
        );

        foreach ($fields as $key) {
            if ($key === 'about_story_image') {
                $existing = trim((string) ($_POST['about_story_image_existing'] ?? ''));
                if (isset($_FILES['about_story_image']) && $_FILES['about_story_image']['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES['about_story_image']['size'] > 1 * 1024 * 1024) {
                        throw new RuntimeException('صورة من نحن: حجم الملف كبير جداً. الحد الأقصى 1 ميجابايت / 1MB.');
                    }
                    $uploadDir = dirname(__DIR__) . '/assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $tmp  = $_FILES['about_story_image']['tmp_name'];
                    $orig = basename($_FILES['about_story_image']['name']);
                    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                        throw new RuntimeException('صورة من نحن: صيغة الملف غير مدعومة.');
                    }
                    $filename = 'about_story_' . time() . '.' . $ext;
                    move_uploaded_file($tmp, $uploadDir . $filename);
                    $st->execute([$key, $filename, $filename]);
                } elseif ($existing !== '') {
                    $st->execute([$key, $existing, $existing]);
                } else {
                    $st->execute([$key, '', '']);
                }
                continue;
            }
            $en = trim((string) ($_POST[$key . '_en'] ?? ''));
            $ar = trim((string) ($_POST[$key . '_ar'] ?? ''));
            $st->execute([$key, $en, $ar]);
        }

        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$data = [];
if ($pdo) {
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value_en, setting_value_ar FROM settings")->fetchAll();
        foreach ($rows as $r) {
            if (in_array($r['setting_key'], $fields, true)) {
                $data[$r['setting_key']] = ['en' => $r['setting_value_en'], 'ar' => $r['setting_value_ar']];
            }
        }
    } catch (Throwable) {}
}

function av(string $key, string $lang, array $data, array $defaults): string {
    $raw = $data[$key][$lang] ?? '';
    if ($raw !== '') return esc($raw);
    $d = $defaults[$key][$lang] ?? '';
    return esc($d);
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1>📄 إعدادات صفحة من نحن</h1>
        <p class="admin-lead" style="margin-bottom:0">عدّل النصوص الظاهرة في صفحة About / من نحن.</p>
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

<form class="admin-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">

    <!-- ═══ Hero Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">🏠 القسم العلوي (Hero)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_hero_title_en" value="<?= av('about_hero_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_hero_lead_en" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_hero_lead', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_hero_title_ar" value="<?= av('about_hero_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_hero_lead_ar" dir="rtl" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_hero_lead', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Story Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">📖 قسم الحكاية (Story)</h2>
        <!-- Story Image -->
        <div style="margin-bottom:1.25rem;">
            <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">🖼️ الصورة الكبيرة — تُظهر في الجانب الأيسر من القسم</label>
            <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
                <div class="about-img-preview" id="about-img-preview"
                     style="width:140px; height:175px; border-radius:10px; border:2px dashed #ddd; background:#f9f9f9; overflow:hidden; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative;"
                     onclick="document.getElementById('file_about_story_image').click()">
                    <?php
                    $imgKey = $data['about_story_image']['en'] ?? '';
                    $imgUrl = $imgKey !== '' ? base_url('assets/uploads/' . $imgKey) : '';
                    if ($imgUrl !== ''): ?>
                        <img src="<?= esc($imgUrl) ?>" id="about-img-tag" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div id="about-img-placeholder" style="text-align:center; color:#bbb;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            <p style="margin:.3rem 0 0; font-size:.75rem;">اضغط لرفع صورة</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="file" id="file_about_story_image" name="about_story_image" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="previewAboutImg(this)">
                    <input type="hidden" name="about_story_image_existing" value="<?= esc($imgKey) ?>">
                    <?php if ($imgKey !== ''): ?>
                        <div style="font-size:0.78rem; color:var(--admin-text-muted); margin-bottom:.4rem;">📎 <?= esc($imgKey) ?></div>
                        <button type="button" onclick="clearAboutImg()" style="background:#fee2e2;border:1px solid #fecaca;color:#dc2626;cursor:pointer;border-radius:6px;padding:.3rem .7rem;font-size:.8rem;">✕ حذف الصورة</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_story_title_en" value="<?= av('about_story_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 1 (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p1_en" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p1', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 2 (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p2_en" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p2', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 3 (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p3_en" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p3', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_story_title_ar" value="<?= av('about_story_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 1 (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p1_ar" dir="rtl" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p1', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 2 (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p2_ar" dir="rtl" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p2', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الفقرة 3 (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_story_p3_ar" dir="rtl" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_story_p3', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Values Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:0.5rem;">💎 قسم القيم (Values)</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.25rem;">اكتب كل قيمة في سطر منفصل بالصيغة: <code>icon | العنوان | الوصف</code>. مثال: <code>💎 | Quality First | We carefully select...</code></p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">القيم (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_values_en" style="flex:1; height:200px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= av('about_values_en', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">القيم (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_values_ar" dir="rtl" style="flex:1; height:200px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= av('about_values_ar', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-top:1rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">عنوان القسم (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_values_title_en" value="<?= av('about_values_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_values_lead_en" value="<?= av('about_values_lead', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">عنوان القسم (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_values_title_ar" value="<?= av('about_values_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_values_lead_ar" value="<?= av('about_values_lead', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Promise Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:0.5rem;">🤝 قسم الوعد (Promise)</h2>
        <p style="color:var(--admin-text-muted); font-size:0.85rem; margin:0 0 1.25rem;">اكتب كل نقطة في سطر منفصل.</p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_promise_title_en" value="<?= av('about_promise_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_promise_text_en" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_promise_text', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (English) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_promises_en" style="flex:1; height:170px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= av('about_promises_en', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="about_promise_title_ar" value="<?= av('about_promise_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_promise_text_ar" dir="rtl" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= av('about_promise_text', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (عربي) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="about_promises_ar" dir="rtl" style="flex:1; height:170px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= av('about_promises_ar', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
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
.btn-clear {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    cursor: pointer;
    border-radius: 6px;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
    transition: .15s;
}
.btn-clear:hover { background: #fecaca; }
@media(max-width:768px){
    form .admin-card div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr !important;}
}
</style>
<script>
function clearField(btn) {
    var wrapper = btn.parentElement;
    var input = wrapper.querySelector('input, textarea');
    if (input) input.value = '';
}
function previewAboutImg(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var box = document.getElementById('about-img-preview');
        var img = document.getElementById('about-img-tag');
        if (!img) {
            box.innerHTML = '';
            img = document.createElement('img');
            img.id = 'about-img-tag';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            box.appendChild(img);
        }
        img.src = e.target.result;
        var ph = document.getElementById('about-img-placeholder');
        if (ph) ph.remove();
        var hidden = document.querySelector('input[name="about_story_image_existing"]');
        if (hidden) hidden.value = '';
    };
    reader.readAsDataURL(input.files[0]);
}
function clearAboutImg() {
    var box = document.getElementById('about-img-preview');
    box.innerHTML = '<div id="about-img-placeholder" style="text-align:center;color:#bbb;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg><p style="margin:.3rem 0 0;font-size:.75rem;">اضغط لرفع صورة</p></div>';
    var inp = document.getElementById('file_about_story_image');
    if (inp) inp.value = '';
    var hidden = document.querySelector('input[name="about_story_image_existing"]');
    if (hidden) hidden.value = '';
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
