<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

$pageTitle = current_lang() === 'ar' ? 'شروط الخدمة' : 'Terms of Service';
$pageDescription = current_lang() === 'ar'
    ? 'شروط وأحكام استخدام موقع زين للعطور وخدماتنا.'
    : 'Terms and conditions for using Zain Perfumes website and services.';
$isAr = current_lang() === 'ar';

require __DIR__ . '/includes/header.php';
?>

<style>
.terms-hero {
    padding: clamp(3rem, 6vw, 5rem) 0 clamp(2rem, 4vw, 3.5rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative; overflow: hidden;
}
.terms-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 70% 55% at 50% 0%, var(--gold-glow), transparent 65%);
    pointer-events: none;
}
.terms-eyebrow {
    display: inline-flex; align-items: center; gap: 0.6rem;
    font-size: 0.72rem; letter-spacing: 0.28em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin-bottom: 1rem; position: relative;
}
.terms-eyebrow span { width: 28px; height: 1px; background: var(--gold); display: inline-block; }
.terms-hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 700; margin: 0 0 0.8rem;
    background: linear-gradient(135deg, var(--gold-bright), var(--gold) 55%, var(--gold-dim));
    -webkit-background-clip: text; background-clip: text; color: transparent;
    line-height: 1.15; position: relative;
}
.terms-hero__lead {
    color: var(--ink-muted); font-size: 1rem;
    max-width: 500px; margin: 0 auto 0.8rem; line-height: 1.7; position: relative;
}
.terms-hero__updated { font-size: 0.78rem; color: var(--ink-muted); opacity: 0.6; position: relative; }

.terms-section {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.terms-inner {
    max-width: 1050px; margin: 0 auto; padding: 0 var(--space);
    display: grid; grid-template-columns: 220px 1fr; gap: 3rem; align-items: start;
}
@media (max-width: 860px) { .terms-inner { grid-template-columns: 1fr; } }

.terms-toc {
    position: sticky; top: 110px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1.4rem;
    box-shadow: var(--shadow-sm);
}
.terms-toc__title {
    font-size: 0.7rem; letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin: 0 0 0.8rem;
}
.terms-toc__list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.terms-toc__list a {
    display: block; color: var(--ink-muted); text-decoration: none;
    font-size: 0.85rem; padding: 0.4rem 0.65rem; border-radius: 8px;
    border-inline-start: 2px solid transparent;
    transition: all 0.2s;
}
.terms-toc__list a:hover {
    color: var(--gold); background: var(--accent-soft);
    border-inline-start-color: var(--gold);
}
@media (max-width: 860px) {
    .terms-toc { position: static; }
    .terms-toc__list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem; }
}
@media (max-width: 480px) { .terms-toc__list { grid-template-columns: 1fr; } }

.terms-block { margin-bottom: 2.8rem; scroll-margin-top: 110px; }
.terms-block:last-child { margin-bottom: 0; }
.terms-block__hdr {
    display: flex; align-items: center; gap: 0.9rem;
    margin-bottom: 1.1rem; padding-bottom: 0.9rem;
    border-bottom: 1px solid var(--border-subtle);
}
.terms-block__icon {
    width: 42px; height: 42px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.22);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.terms-block__title {
    font-family: var(--font-serif);
    font-size: 1.2rem; font-weight: 700; color: var(--ink); margin: 0;
}
.terms-text { color: var(--ink-muted); font-size: 0.96rem; line-height: 1.8; margin: 0 0 0.9rem; }
.terms-text:last-child { margin-bottom: 0; }
.terms-text a { color: var(--gold); text-decoration: underline; }

.terms-list { list-style: none; padding: 0; margin: 0 0 0.9rem; display: flex; flex-direction: column; gap: 0.6rem; }
.terms-list li {
    display: flex; align-items: flex-start; gap: 0.7rem;
    color: var(--ink-muted); font-size: 0.96rem; line-height: 1.65;
}
.terms-list li::before {
    content: '•'; color: var(--gold); font-size: 1.2rem;
    line-height: 1.1; flex-shrink: 0;
}

.terms-note {
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.25);
    border-inline-start: 3px solid var(--gold);
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin: 0.8rem 0;
    color: var(--ink-muted);
    font-size: 0.9rem; line-height: 1.7;
}
.terms-note strong { color: var(--gold); }

.terms-cta {
    background: linear-gradient(135deg, var(--bg-elevated), var(--bg-warm, #fdfaf5));
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 2.2rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.terms-cta h3 {
    font-family: var(--font-serif);
    font-size: 1.25rem; font-weight: 700; color: var(--ink); margin: 0 0 0.5rem;
}
.terms-cta p { color: var(--ink-muted); font-size: 0.9rem; margin: 0 0 1.5rem; line-height: 1.6; }
.terms-cta__btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.terms-cta__btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.8rem 1.5rem; border-radius: var(--radius-full);
    font-weight: 700; font-size: 0.88rem; text-decoration: none;
    transition: all 0.25s ease;
}
.terms-cta__btn--primary {
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508 !important;
    box-shadow: 0 4px 16px var(--gold-glow);
}
.terms-cta__btn--primary:hover { filter: brightness(1.08); transform: translateY(-2px); }
.terms-cta__btn--secondary {
    background: var(--bg); border: 1px solid var(--border); color: var(--ink-muted) !important;
}
.terms-cta__btn--secondary:hover { border-color: var(--gold); color: var(--gold) !important; }
</style>

<section class="terms-hero">
    <div class="container" style="position:relative;z-index:1;">
        <p class="terms-eyebrow"><span></span><?= $isAr ? 'سياساتنا' : 'Our Policies' ?><span></span></p>
        <h1><?= $isAr ? 'شروط الخدمة' : 'Terms of Service' ?></h1>
        <p class="terms-hero__lead">
            <?= $isAr
                ? 'يرجى قراءة شروط الخدمة بعناية قبل استخدام موقعنا. باستخدامك للموقع فإنك توافق على هذه الشروط.'
                : 'Please read these terms of service carefully before using our website. By using the site, you agree to these terms.' ?>
        </p>
        <p class="terms-hero__updated"><?= $isAr ? 'آخر تحديث: يونيو ٢٠٢٦' : 'Last updated: June 2026' ?></p>
    </div>
</section>

<section class="terms-section">
    <div class="terms-inner">

        <aside class="terms-toc">
            <p class="terms-toc__title"><?= $isAr ? 'محتويات الصفحة' : 'On This Page' ?></p>
            <ul class="terms-toc__list">
                <li><a href="#account"><?= $isAr ? 'شروط الحساب' : 'Account Terms' ?></a></li>
                <li><a href="#payment"><?= $isAr ? 'شروط الدفع' : 'Payment Terms' ?></a></li>
                <li><a href="#delivery"><?= $isAr ? 'التوصيل والشحن' : 'Delivery & Shipping' ?></a></li>
                <li><a href="#returns"><?= $isAr ? 'الإرجاع والاستبدال' : 'Returns & Exchange' ?></a></li>
                <li><a href="#liability"><?= $isAr ? 'حدود المسؤولية' : 'Liability Limitations' ?></a></li>
                <li><a href="#intellectual"><?= $isAr ? 'الملكية الفكرية' : 'Intellectual Property' ?></a></li>
                <li><a href="#modifications"><?= $isAr ? 'تعديل الشروط' : 'Modification of Terms' ?></a></li>
                <li><a href="#contact"><?= $isAr ? 'تواصل معنا' : 'Contact Us' ?></a></li>
            </ul>
        </aside>

        <div>

            <div class="terms-block" id="account">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">👤</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'شروط الحساب' : 'Account Terms' ?></h2>
                </div>
                <ul class="terms-list">
                    <?php
                    $accountItems = $isAr
                        ? ["يجب تقديم معلومات صحيحة ودقيقة عند إنشاء الحساب", "أنت مسؤول عن الحفاظ على سرية بيانات حسابك وكلمة المرور", "يحق لنا تعليق أو إنهاء الحسابات التي تقدم معلومات غير صحيحة أو تنتهك الشروط", "يجب أن يكون عمرك 18 عاماً على الأقل لإنشاء حساب وإجراء عمليات شراء"]
                        : ["You must provide accurate and correct information when creating an account", "You are responsible for keeping your account credentials and password confidential", "We reserve the right to suspend or terminate accounts with false information or that violate the terms", "You must be at least 18 years old to create an account and make purchases"];
                    foreach ($accountItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="terms-block" id="payment">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">💳</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'شروط الدفع' : 'Payment Terms' ?></h2>
                </div>
                <ul class="terms-list">
                    <?php
                    $paymentItems = $isAr
                        ? ["جميع الأسعار معروضة بالجنيه المصري (ج.م) وتشمل ضريبة القيمة المضافة", "طرق الدفع المتاحة: الدفع عند الاستلام، التحويل البنكي، المحافظ الإلكترونية", "يجب إتمام الدفع خلال 24 ساعة من تأكيد الطلب لطلبات التحويل البنكي", "قد نطلب تأكيداً إضافياً للهوية في بعض الحالات لضمان أمان المعاملة"]
                        : ["All prices are in Egyptian Pounds (EGP) and include VAT", "Available payment methods: Cash on Delivery, Bank Transfer, Digital Wallets", "Payment must be completed within 24 hours of order confirmation for bank transfer orders", "We may request additional identity verification in some cases to ensure transaction security"];
                    foreach ($paymentItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="terms-block" id="delivery">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">🚚</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'التوصيل والشحن' : 'Delivery & Shipping' ?></h2>
                </div>
                <ul class="terms-list">
                    <?php
                    $deliveryItems = $isAr
                        ? ["مدة التوصيل من 2 إلى 5 أيام عمل داخل مصر", "رسوم الشحن تحسب بناءً على منطقة التوصيل وتظهر عند إتمام الطلب", "التوصيل مجاني للطلبات التي تزيد قيمتها عن 1000 ج.م داخل القاهرة", "نحن غير مسؤولين عن التأخير الناتج عن ظروف خارجة عن إرادتنا (مثل سوء الأحوال الجوية أو الأعياد)"]
                        : ["Delivery time is 2 to 5 business days within Egypt", "Shipping fees are calculated based on delivery area and shown at checkout", "Free delivery for orders over 1000 EGP within Cairo", "We are not responsible for delays caused by circumstances beyond our control (such as severe weather or holidays)"];
                    foreach ($deliveryItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="terms-block" id="returns">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">🔄</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'الإرجاع والاستبدال' : 'Returns & Exchange' ?></h2>
                </div>
                <p class="terms-text">
                    <?= $isAr
                        ? 'تخضع عمليات الإرجاع والاستبدال لـ <a href="' . esc(url('policy.php')) . '">سياسة الاستبدال والاسترجاع</a> الخاصة بنا. نوصي بقراءة السياسة كاملة قبل إتمام أي عملية شراء.'
                        : 'Returns and exchanges are subject to our <a href="' . esc(url('policy.php')) . '">Return & Exchange Policy</a>. We recommend reading the full policy before completing any purchase.' ?>
                </p>
                <div class="terms-note">
                    <strong><?= $isAr ? 'ملخص:' : 'Summary:' ?></strong>
                    <?= $isAr
                        ? ' يمكن استبدال المنتجات خلال 7 أيام من الاستلام بشرط أن تكون غير مستخدمة وفي عبوتها الأصلية.'
                        : ' Products can be exchanged within 7 days of receipt provided they are unused and in original packaging.' ?>
                </div>
            </div>

            <div class="terms-block" id="liability">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">⚖️</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'حدود المسؤولية' : 'Liability Limitations' ?></h2>
                </div>
                <ul class="terms-list">
                    <?php
                    $liabilityItems = $isAr
                        ? ["نحن نبذل قصارى جهدنا لضمان دقة معلومات المنتجات وأسعارها، لكن قد تحدث أخطاء غير مقصودة", "نحن غير مسؤولين عن أي أضرار غير مباشرة أو عرضية ناتجة عن استخدام المنتجات", "في حالة وجود خطأ في السعر، نحتفظ بالحق في إلغاء الطلب وإعادة المبلغ بالكامل", "جميع المنتجات تباع 'كما هي' ما لم يذكر خلاف ذلك في وصف المنتج"]
                        : ["We make every effort to ensure accuracy of product information and pricing, but unintentional errors may occur", "We are not liable for any indirect or incidental damages resulting from product use", "In case of a pricing error, we reserve the right to cancel the order and refund the full amount", "All products are sold 'as is' unless otherwise stated in the product description"];
                    foreach ($liabilityItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="terms-block" id="intellectual">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">©️</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'الملكية الفكرية' : 'Intellectual Property' ?></h2>
                </div>
                <p class="terms-text">
                    <?= $isAr
                        ? 'جميع المحتويات الموجودة على هذا الموقع — بما في ذلك النصوص والصور والشعارات والتصاميم والعلامات التجارية — هي ملكية حصرية لزين للعطور ومحمية بموجب قوانين الملكية الفكرية.'
                        : 'All content on this website — including text, images, logos, designs, and trademarks — is the exclusive property of Zain Perfumes and is protected by intellectual property laws.' ?>
                </p>
                <ul class="terms-list">
                    <?php
                    $ipItems = $isAr
                        ? ["لا يجوز نسخ أو إعادة إنتاج أو توزيع أي محتوى دون إذن كتابي مسبق", "اسم 'زين للعطور' والشعار علامات تجارية مسجلة", "يمكنكم مشاركة روابط صفحاتنا عبر وسائل التواصل الاجتماعي"]
                        : ["No content may be copied, reproduced, or distributed without prior written permission", "The name 'Zain Perfumes' and logo are registered trademarks", "You may share links to our pages on social media"];
                    foreach ($ipItems as $item): ?>
                    <li><?= esc($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="terms-block" id="modifications">
                <div class="terms-block__hdr">
                    <div class="terms-block__icon">📝</div>
                    <h2 class="terms-block__title"><?= $isAr ? 'تعديل الشروط' : 'Modification of Terms' ?></h2>
                </div>
                <p class="terms-text">
                    <?= $isAr
                        ? 'نحتفظ بالحق في تعديل أو تحديث شروط الخدمة في أي وقت. سيتم نشر التعديلات على هذه الصفحة ويصبح ساري المفعول فور نشرها. استمرارك في استخدام الموقع بعد التعديلات يعتبر موافقة منك على الشروط المعدلة. ننصح بمراجعة هذه الصفحة بشكل دوري.'
                        : 'We reserve the right to modify or update these terms of service at any time. Changes will be posted on this page and become effective immediately upon posting. Your continued use of the site after modifications constitutes your acceptance of the revised terms. We recommend reviewing this page periodically.' ?>
                </p>
            </div>

            <div class="terms-block" id="contact">
                <div class="terms-cta">
                    <h3><?= $isAr ? 'هل لديك سؤال عن الشروط؟' : 'Have a Question About Our Terms?' ?></h3>
                    <p>
                        <?= $isAr
                            ? 'إذا كان لديك أي استفسار، فريقنا جاهز للإجابة.'
                            : 'If you have any questions, our team is ready to help.' ?>
                    </p>
                    <div class="terms-cta__btns">
                        <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" class="terms-cta__btn terms-cta__btn--primary">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24z"/></svg>
                            WhatsApp
                        </a>
                        <a href="<?= esc(url('contact.php')) ?>" class="terms-cta__btn terms-cta__btn--secondary">
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