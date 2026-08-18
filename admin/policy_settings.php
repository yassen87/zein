<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

$pageTitle = 'إعدادات صفحة سياسة الارجاع';

$pdo = medal_pdo();
$success = false;
$error   = '';

$fields = [
    'policy_hero_title',
    'policy_hero_lead',
    'policy_hero_updated',
    'policy_exchange_title',
    'policy_exchange_badge',
    'policy_exchange_text',
    'policy_exchange_list_en',
    'policy_exchange_list_ar',
    'policy_exchange_note',
    'policy_returns_title',
    'policy_returns_lead',
    'policy_returns_list_en',
    'policy_returns_list_ar',
    'policy_returns_note',
    'policy_shipping_title',
    'policy_shipping_list_en',
    'policy_shipping_list_ar',
    'policy_contact_title',
    'policy_contact_text',
];

$defaults = [
    'policy_hero_title'      => ['en' => 'Return & Exchange Policy',              'ar' => 'سياسة الاستبدال والارتجاع'],
    'policy_hero_lead'       => ['en' => "We want your experience to always be excellent. Read our policy to know your full rights.", 'ar' => 'نريد أن تكون تجربتك ممتازة دائمًا. اقرأ سياستك لتعرف حقوقك كاملة.'],
    'policy_hero_updated'    => ['en' => 'Last updated: May 2025',                 'ar' => 'آخر تحديث: مايو ٢٠٢٥'],
    'policy_exchange_title'  => ['en' => 'Exchange Policy',                        'ar' => 'سياسة الاستبدال'],
    'policy_exchange_badge'  => ['en' => 'Within 7 days',                          'ar' => 'خلال ٧ أيام'],
    'policy_exchange_text'   => ['en' => 'We offer product exchange within <strong style="color:var(--gold);">7 days</strong> of receipt, under the conditions outlined below.', 'ar' => 'نوفر إمكانية استبدال المنتجات خلال <strong style="color:var(--gold);">٧ أيام</strong> من تاريخ الاستلام وفق الشروط الموضحة أدناه.'],
    'policy_exchange_list_en'=> ['en' => "Product unused and in its original sealed packaging\nExchange for equal or higher value product (difference adjusted)\nPresent original purchase invoice or order number\nContact support team first before sending the product", 'ar' => ''],
    'policy_exchange_list_ar'=> ['ar' => "المنتج غير مستخدم وفي عبوته الأصلية المغلقة\nالاستبدال بمنتج بنفس القيمة أو أعلى (مع تسوية الفرق)\nتقديم فاتورة الشراء الأصلية أو رقم الطلب\nالتواصل مع فريق الدعم أولاً قبل إرسال المنتج", 'en' => ''],
    'policy_exchange_note'   => ['en' => 'The period is calculated from the actual delivery date, not the order date.', 'ar' => 'يُحسب الوقت من تاريخ التسليم الفعلي لا من تاريخ الطلب.'],
    'policy_returns_title'   => ['en' => 'Returns & Refunds',                      'ar' => 'الاسترجاع والإرجاع'],
    'policy_returns_lead'    => ['en' => 'We accept return requests only in the following cases:', 'ar' => 'نقبل طلبات الإرجاع في الحالات التالية فقط:'],
    'policy_returns_list_en' => ['en' => "Receiving a damaged or broken product due to shipping\nReceiving a different product than ordered (error on our part)\nA product defect not mentioned in the description", 'ar' => ''],
    'policy_returns_list_ar' => ['ar' => "استلام منتج تالف أو مكسور بسبب الشحن\nاستلام منتج مختلف عن المطلوب (خطأ من جانبنا)\nوجود عيب في المنتج لم يُذكر في الوصف", 'en' => ''],
    'policy_returns_note'    => ['en' => 'Please contact us immediately and send photos of the product to review and take appropriate action.', 'ar' => 'يُرجى التواصل فوراً وإرسال صور للمنتج لمراجعة الطلب واتخاذ الإجراء المناسب.'],
    'policy_shipping_title'  => ['en' => 'Exchange Shipping Costs',                 'ar' => 'تكاليف شحن الاستبدال'],
    'policy_shipping_list_en'=> ['en' => "Error on our part (wrong or damaged): we cover full shipping cost\nExchange for personal reasons: customer covers return shipping\nReplacement product shipping: free in all accepted exchange cases", 'ar' => ''],
    'policy_shipping_list_ar'=> ['ar' => "خطأ من جانبنا (منتج خاطئ أو تالف): نتحمل تكاليف الشحن كاملة\nاستبدال لأسباب شخصية: يتحمل العميل تكاليف شحن الإرجاع\nشحن المنتج البديل: مجاني في جميع حالات الاستبدال المقبولة", 'en' => ''],
    'policy_contact_title'   => ['en' => 'Have a Question About Our Policy?',       'ar' => 'هل لديك سؤال عن سياستنا؟'],
    'policy_contact_text'    => ['en' => "Our team is ready to help. Contact us via WhatsApp or our contact form.", 'ar' => 'فريقنا جاهز للمساعدة. تواصل معنا عبر واتساب أو نموذج التواصل.'],
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

function pv(string $key, string $lang, array $data, array $defaults): string {
    $raw = $data[$key][$lang] ?? '';
    if ($raw !== '') return esc($raw);
    $d = $defaults[$key][$lang] ?? '';
    return esc($d);
}

require __DIR__ . '/_layout_start.php';
?>

<div class="admin-header-actions">
    <div>
        <h1>📄 إعدادات صفحة سياسة الارجاع</h1>
        <p class="admin-lead" style="margin-bottom:0">عدّل النصوص الظاهرة في صفحة Return &amp; Exchange Policy.</p>
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

<form class="admin-form" method="post">
    <input type="hidden" name="csrf" value="<?= esc(admin_csrf_token()) ?>">

    <!-- ═══ Hero Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">🏠 القسم العلوي (Hero)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_hero_title_en" value="<?= pv('policy_hero_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_hero_lead_en" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_hero_lead', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">تاريخ التحديث (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_hero_updated_en" value="<?= pv('policy_hero_updated', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_hero_title_ar" value="<?= pv('policy_hero_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_hero_lead_ar" dir="rtl" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_hero_lead', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">تاريخ التحديث (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_hero_updated_ar" value="<?= pv('policy_hero_updated', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Exchange Policy ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">🔄 سياسة الاستبدال (Exchange Policy)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_exchange_title_en" value="<?= pv('policy_exchange_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الوسام / Badge (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_exchange_badge_en" value="<?= pv('policy_exchange_badge', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص الرئيسي (English) — يمكن استخدام HTML</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_text_en" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_exchange_text', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (English) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_list_en" style="flex:1; height:130px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_exchange_list_en', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الملاحظة (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_note_en" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_exchange_note', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_exchange_title_ar" value="<?= pv('policy_exchange_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الوسام / Badge (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_exchange_badge_ar" value="<?= pv('policy_exchange_badge', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص الرئيسي (عربي) — يمكن استخدام HTML</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_text_ar" dir="rtl" style="flex:1; height:80px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_exchange_text', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (عربي) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_list_ar" dir="rtl" style="flex:1; height:130px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_exchange_list_ar', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الملاحظة (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_exchange_note_ar" dir="rtl" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_exchange_note', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Returns Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">↩️ الاسترجاع والإرجاع (Returns &amp; Refunds)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_returns_title_en" value="<?= pv('policy_returns_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_lead_en" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_returns_lead', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (English) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_list_en" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_returns_list_en', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الملاحظة (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_note_en" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_returns_note', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_returns_title_ar" value="<?= pv('policy_returns_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص التعريفي (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_lead_ar" dir="rtl" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_returns_lead', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (عربي) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_list_ar" dir="rtl" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_returns_list_ar', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">الملاحظة (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_returns_note_ar" dir="rtl" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_returns_note', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Shipping Section ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">🚚 تكاليف الشحن (Shipping Costs)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_shipping_title_en" value="<?= pv('policy_shipping_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (English) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_shipping_list_en" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_shipping_list_en', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_shipping_title_ar" value="<?= pv('policy_shipping_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النقاط (عربي) — سطر لكل نقطة</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_shipping_list_ar" dir="rtl" style="flex:1; height:100px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box; font-family:monospace; font-size:.85rem;"><?= pv('policy_shipping_list_ar', 'ar', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Contact CTA ═══ -->
    <div class="admin-card" style="padding:1.75rem; margin-bottom:2rem; border:1px solid var(--admin-card-border);">
        <h2 style="margin-top:0; font-size:1.15rem; margin-bottom:1.25rem;">📞 قسم التواصل (Contact CTA)</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (English)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_contact_title_en" value="<?= pv('policy_contact_title', 'en', $data, $defaults) ?>" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص (English)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_contact_text_en" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_contact_text', 'en', $data, $defaults) ?></textarea>
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح" style="margin-top:0;">✕</button>
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem;">العنوان (عربي)</label>
                <div style="display:flex; gap:0.5rem;">
                    <input type="text" name="policy_contact_title_ar" value="<?= pv('policy_contact_title', 'ar', $data, $defaults) ?>" dir="rtl" style="flex:1; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;">
                    <button type="button" class="btn-clear" onclick="clearField(this)" title="مسح">✕</button>
                </div>
                <label style="display:block; margin:.8rem 0 .5rem; font-weight:600; font-size:.88rem;">النص (عربي)</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <textarea name="policy_contact_text_ar" dir="rtl" style="flex:1; height:60px; padding:.7rem; border:1px solid var(--admin-input-border); border-radius:8px; box-sizing:border-box;"><?= pv('policy_contact_text', 'ar', $data, $defaults) ?></textarea>
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
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>