<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

$pageTitle = current_lang() === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy';
$pageDescription = current_lang() === 'ar'
    ? 'تعرف على كيفية جمع واستخدام بياناتك الشخصية وحقوقك.'
    : 'Learn how we collect, use, and protect your personal data and your rights.';
$isAr = current_lang() === 'ar';

require __DIR__ . '/includes/header.php';
?>

<style>
.privacy-hero {
    padding: clamp(3rem, 6vw, 5rem) 0 clamp(2rem, 4vw, 3.5rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative; overflow: hidden;
}
.privacy-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 70% 55% at 50% 0%, var(--gold-glow), transparent 65%);
    pointer-events: none;
}
.privacy-eyebrow {
    display: inline-flex; align-items: center; gap: 0.6rem;
    font-size: 0.72rem; letter-spacing: 0.28em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin-bottom: 1rem; position: relative;
}
.privacy-eyebrow span { width: 28px; height: 1px; background: var(--gold); display: inline-block; }
.privacy-hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 700; margin: 0 0 0.8rem;
    background: linear-gradient(135deg, var(--gold-bright), var(--gold) 55%, var(--gold-dim));
    -webkit-background-clip: text; background-clip: text; color: transparent;
    line-height: 1.15; position: relative;
}
.privacy-hero__lead {
    color: var(--ink-muted); font-size: 1rem;
    max-width: 500px; margin: 0 auto 0.8rem; line-height: 1.7; position: relative;
}
.privacy-hero__updated { font-size: 0.78rem; color: var(--ink-muted); opacity: 0.6; position: relative; }

.privacy-section {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.privacy-inner {
    max-width: 1050px; margin: 0 auto; padding: 0 var(--space);
    display: grid; grid-template-columns: 220px 1fr; gap: 3rem; align-items: start;
}
@media (max-width: 860px) { .privacy-inner { grid-template-columns: 1fr; } }

.privacy-toc {
    position: sticky; top: 110px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1.4rem;
    box-shadow: var(--shadow-sm);
}
.privacy-toc__title {
    font-size: 0.7rem; letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin: 0 0 0.8rem;
}
.privacy-toc__list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.privacy-toc__list a {
    display: block; color: var(--ink-muted); text-decoration: none;
    font-size: 0.85rem; padding: 0.4rem 0.65rem; border-radius: 8px;
    border-inline-start: 2px solid transparent;
    transition: all 0.2s;
}
.privacy-toc__list a:hover {
    color: var(--gold); background: var(--accent-soft);
    border-inline-start-color: var(--gold);
}
@media (max-width: 860px) {
    .privacy-toc { position: static; }
    .privacy-toc__list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem; }
}
@media (max-width: 480px) { .privacy-toc__list { grid-template-columns: 1fr; } }

.privacy-block { margin-bottom: 2.8rem; scroll-margin-top: 110px; }
.privacy-block:last-child { margin-bottom: 0; }
.privacy-block__hdr {
    display: flex; align-items: center; gap: 0.9rem;
    margin-bottom: 1.1rem; padding-bottom: 0.9rem;
    border-bottom: 1px solid var(--border-subtle);
}
.privacy-block__icon {
    width: 42px; height: 42px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.22);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.privacy-block__title {
    font-family: var(--font-serif);
    font-size: 1.2rem; font-weight: 700; color: var(--ink); margin: 0;
}
.privacy-text { color: var(--ink-muted); font-size: 0.96rem; line-height: 1.8; margin: 0 0 0.9rem; }
.privacy-text:last-child { margin-bottom: 0; }

.privacy-list { list-style: none; padding: 0; margin: 0 0 0.9rem; display: flex; flex-direction: column; gap: 0.6rem; }
.privacy-list li {
    display: flex; align-items: flex-start; gap: 0.7rem;
    color: var(--ink-muted); font-size: 0.96rem; line-height: 1.65;
}
.privacy-list li::before {
    content: '•'; color: var(--gold); font-size: 1.2rem;
    line-height: 1.1; flex-shrink: 0;
}

.privacy-note {
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.25);
    border-inline-start: 3px solid var(--gold);
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin: 0.8rem 0;
    color: var(--ink-muted);
    font-size: 0.9rem; line-height: 1.7;
}
.privacy-note strong { color: var(--gold); }

.privacy-cta {
    background: linear-gradient(135deg, var(--bg-elevated), var(--bg-warm, #fdfaf5));
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 2.2rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.privacy-cta h3 {
    font-family: var(--font-serif);
    font-size: 1.25rem; font-weight: 700; color: var(--ink); margin: 0 0 0.5rem;
}
.privacy-cta p { color: var(--ink-muted); font-size: 0.9rem; margin: 0 0 1.5rem; line-height: 1.6; }
.privacy-cta__btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.privacy-cta__btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.8rem 1.5rem; border-radius: var(--radius-full);
    font-weight: 700; font-size: 0.88rem; text-decoration: none;
    transition: all 0.25s ease;
}
.privacy-cta__btn--primary {
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508 !important;
    box-shadow: 0 4px 16px var(--gold-glow);
}
.privacy-cta__btn--primary:hover { filter: brightness(1.08); transform: translateY(-2px); }
.privacy-cta__btn--secondary {
    background: var(--bg); border: 1px solid var(--border); color: var(--ink-muted) !important;
}
.privacy-cta__btn--secondary:hover { border-color: var(--gold); color: var(--gold) !important; }
</style>

<section class="privacy-hero">
    <div class="container" style="position:relative;z-index:1;">
        <p class="privacy-eyebrow"><span></span><?= $isAr ? 'سياساتنا' : 'Our Policies' ?><span></span></p>
        <h1><?= $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' ?></h1>
        <p class="privacy-hero__lead">
            <?= $isAr
                ? 'خصوصيتك تهمنا. نوضح هنا كيفية جمع واستخدام وحماية بياناتك الشخصية.'
                : 'Your privacy matters to us. Here we explain how we collect, use, and protect your personal data.' ?>
        </p>
        <p class="privacy-hero__updated"><?= $isAr ? 'آخر تحديث: يونيو ٢٠٢٦' : 'Last updated: June 2026' ?></p>
    </div>
</section>

<section class="privacy-section">
    <div class="privacy-inner">

        <aside class="privacy-toc">
            <p class="privacy-toc__title"><?= $isAr ? 'محتويات الصفحة' : 'On This Page' ?></p>
            <ul class="privacy-toc__list">
                <li><a href="#data-collected"><?= $isAr ? 'البيانات التي نجمعها' : 'Data We Collect' ?></a></li>
                <li><a href="#data-usage"><?= $isAr ? 'كيفية استخدام البيانات' : 'How We Use Data' ?></a></li>
                <li><a href="#data-retention"><?= $isAr ? 'مدة الاحتفاظ بالبيانات' : 'Data Retention' ?></a></li>
                <li><a href="#third-party"><?= $isAr ? 'مشاركة البيانات' : 'Third-Party Sharing' ?></a></li>
                <li><a href="#cookies"><?= $isAr ? 'سياسة الكوكيز' : 'Cookie Policy' ?></a></li>
                <li><a href="#user-rights"><?= $isAr ? 'حقوق المستخدم' : 'User Rights' ?></a></li>
                <li><a href="#contact"><?= $isAr ? 'معلومات الاتصال' : 'Contact Information' ?></a></li>
            </ul>
        </aside>

        <div>

            <div class="privacy-block" id="data-collected">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">📋</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'البيانات التي نجمعها' : 'Data We Collect' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'عند استخدامك لموقعنا أو إجراء عملية شراء، قد نقوم بجمع المعلومات التالية:'
                        : 'When you use our website or make a purchase, we may collect the following information:' ?>
                </p>
                <ul class="privacy-list">
                    <?php
                    $dataItems = $isAr
                        ? ["الاسم الكامل", "رقم الهاتف", "البريد الإلكتروني", "عنوان الشحن (المدينة، المنطقة، العنوان التفصيلي)", "تفاصيل الطلبات وسجل المشتريات"]
                        : ["Full name", "Phone number", "Email address", "Shipping address (city, area, detailed address)", "Order details and purchase history"];
                    foreach ($dataItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="privacy-note">
                    <strong><?= $isAr ? 'ملاحظة:' : 'Note:' ?></strong>
                    <?= $isAr
                        ? ' لا نقوم بجمع أو تخزين معلومات الدفع مثل أرقام بطاقات الائتمان. تتم عمليات الدفع من خلال بوابات دفع آمنة.'
                        : ' We do not collect or store payment information such as credit card numbers. Payment processing is handled through secure payment gateways.' ?>
                </div>
            </div>

            <div class="privacy-block" id="data-usage">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">⚙️</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'كيفية استخدام البيانات' : 'How We Use Data' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'نستخدم بياناتك الشخصية للأغراض التالية فقط:'
                        : 'We use your personal data only for the following purposes:' ?>
                </p>
                <ul class="privacy-list">
                    <?php
                    $usageItems = $isAr
                        ? ["معالجة الطلبات وتوصيل المنتجات إليك", "التواصل معك بخصوص طلبك (تأكيد، تحديثات، شحن)", "الرد على استفساراتك وطلبات الدعم", "إرسال العروض والتحديثات التسويقية (فقط بموافقتك)", "تحسين تجربة المستخدم وتطوير خدماتنا"]
                        : ["Processing orders and delivering products to you", "Communicating with you about your order (confirmation, updates, shipping)", "Responding to your inquiries and support requests", "Sending promotional offers and updates (only with your consent)", "Improving user experience and developing our services"];
                    foreach ($usageItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="privacy-block" id="data-retention">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">🗄️</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'مدة الاحتفاظ بالبيانات' : 'Data Retention' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'نحتفظ ببياناتك الشخصية طالما كان حسابك نشطاً أو طالما كان ذلك ضرورياً لتقديم خدماتنا. يتم الاحتفاظ بسجل الطلبات لمدة لا تقل عن سنتين لأغراض محاسبية وقانونية. يمكنك طلب حذف بياناتك في أي وقت بالتواصل معنا.'
                        : 'We retain your personal data as long as your account is active or as necessary to provide our services. Order records are kept for at least two years for accounting and legal purposes. You may request deletion of your data at any time by contacting us.' ?>
                </p>
            </div>

            <div class="privacy-block" id="third-party">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">🔗</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'مشاركة البيانات مع أطراف خارجية' : 'Third-Party Sharing' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'نحن لا نبيع أو نتاجر ببياناتك الشخصية. قد نشارك بياناتك مع أطراف خارجية فقط في الحالات التالية:'
                        : 'We do not sell or trade your personal data. We may share your data with third parties only in the following cases:' ?>
                </p>
                <ul class="privacy-list">
                    <?php
                    $shareItems = $isAr
                        ? ["شركات الشحن والتوصيل لتسليم طلبك", "مزودي خدمات الدفع الإلكتروني لمعالجة المدفوعات", "الجهات القانونية عند الحاجة للامتثال للقوانين واللوائح"]
                        : ["Shipping and delivery companies to deliver your order", "Payment service providers to process payments", "Legal authorities when required to comply with laws and regulations"];
                    foreach ($shareItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="privacy-note">
                    <strong><?= $isAr ? 'ملاحظة:' : 'Note:' ?></strong>
                    <?= $isAr
                        ? ' جميع الأطراف التي نتعامل معها ملزمة بعقود تحمي خصوصية بياناتك.'
                        : ' All parties we work with are bound by contracts that protect your data privacy.' ?>
                </div>
            </div>

            <div class="privacy-block" id="cookies">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">🍪</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'سياسة الكوكيز' : 'Cookie Policy' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'يستخدم موقعنا ملفات تعريف الارتباط (الكوكيز) لتحسين تجربة التصفح. تشمل استخدامات الكوكيز:'
                        : 'Our website uses cookies to improve your browsing experience. Cookie uses include:' ?>
                </p>
                <ul class="privacy-list">
                    <?php
                    $cookieItems = $isAr
                        ? ["تذكر تفضيلات اللغة (العربية / الإنجليزية)", "الحفاظ على جلسة التصفح وسلة التسوق", "تحليل استخدام الموقع لتحسين الأداء"]
                        : ["Remembering language preferences (Arabic / English)", "Maintaining browsing session and shopping cart", "Analyzing site usage to improve performance"];
                    foreach ($cookieItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'يمكنك تعطيل الكوكيز من إعدادات المتصفح، لكن قد يؤثر ذلك على بعض وظائف الموقع.'
                        : 'You can disable cookies in your browser settings, but this may affect some site functionality.' ?>
                </p>
            </div>

            <div class="privacy-block" id="user-rights">
                <div class="privacy-block__hdr">
                    <div class="privacy-block__icon">🛡️</div>
                    <h2 class="privacy-block__title"><?= $isAr ? 'حقوق المستخدم' : 'User Rights' ?></h2>
                </div>
                <p class="privacy-text">
                    <?= $isAr
                        ? 'لديك الحقوق التالية فيما يتعلق ببياناتك الشخصية:'
                        : 'You have the following rights regarding your personal data:' ?>
                </p>
                <ul class="privacy-list">
                    <?php
                    $rightsItems = $isAr
                        ? ["الحق في الوصول: يمكنك طلب نسخة من بياناتك الشخصية", "الحق في التصحيح: يمكنك طلب تصحيح أي بيانات غير دقيقة", "الحق في الحذف: يمكنك طلب حذف بياناتك الشخصية", "الحق في الاعتراض: يمكنك الاعتراض على معالجة بياناتك لأغراض تسويقية", "الحق في سحب الموافقة: يمكنك سحب موافقتك في أي وقت"]
                        : ["Right to access: You can request a copy of your personal data", "Right to rectification: You can request correction of inaccurate data", "Right to erasure: You can request deletion of your personal data", "Right to object: You can object to processing of your data for marketing purposes", "Right to withdraw consent: You can withdraw your consent at any time"];
                    foreach ($rightsItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="privacy-block" id="contact">
                <div class="privacy-cta">
                    <h3><?= $isAr ? 'هل لديك سؤال عن سياسة الخصوصية؟' : 'Have a Question About Our Privacy Policy?' ?></h3>
                    <p>
                        <?= $isAr
                            ? 'إذا كان لديك أي استفسار أو طلب بخصوص بياناتك، لا تتردد في التواصل معنا.'
                            : 'If you have any questions or requests regarding your data, feel free to contact us.' ?>
                    </p>
                    <div class="privacy-cta__btns">
                        <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" class="privacy-cta__btn privacy-cta__btn--primary">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24z"/></svg>
                            WhatsApp
                        </a>
                        <a href="<?= esc(url('contact.php')) ?>" class="privacy-cta__btn privacy-cta__btn--secondary">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <?= $isAr ? 'نموذج التواصل' : 'Contact Form' ?>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>