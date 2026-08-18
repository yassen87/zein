<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

$pageTitle = current_lang() === 'ar' ? 'سياسة الاستبدال والارتجاع' : 'Return & Exchange Policy';
$pageDescription = current_lang() === 'ar' ? 'سياسة الاستبدال والارتجاع من زين للعطور - شروط الاستبدال خلال ٧ أيام، الإرجاع، وتكاليف الشحن.' : 'Zain Perfumes Return & Exchange Policy - Exchange within 7 days, refunds, and shipping costs.';
$canonicalUrl = get_current_url_without_lang();
$isAr = current_lang() === 'ar';

require __DIR__ . '/includes/header.php';
?>

<style>
/* ── Policy Page ── */
.policy-hero {
    padding: clamp(3rem, 6vw, 5rem) 0 clamp(2rem, 4vw, 3.5rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative; overflow: hidden;
}
.policy-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 70% 55% at 50% 0%, var(--gold-glow), transparent 65%);
    pointer-events: none;
}
.policy-eyebrow {
    display: inline-flex; align-items: center; gap: 0.6rem;
    font-size: 0.72rem; letter-spacing: 0.28em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin-bottom: 1rem; position: relative;
}
.policy-eyebrow span { width: 28px; height: 1px; background: var(--gold); display: inline-block; }
.policy-hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 700; margin: 0 0 0.8rem;
    background: linear-gradient(135deg, var(--gold-bright), var(--gold) 55%, var(--gold-dim));
    -webkit-background-clip: text; background-clip: text; color: transparent;
    line-height: 1.15; position: relative;
}
.policy-hero__lead {
    color: var(--ink-muted); font-size: 1rem;
    max-width: 500px; margin: 0 auto 0.8rem; line-height: 1.7; position: relative;
}
.policy-hero__updated { font-size: 0.78rem; color: var(--ink-muted); opacity: 0.6; position: relative; }

/* ── Layout ── */
.policy-section {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.policy-inner {
    max-width: 1050px; margin: 0 auto; padding: 0 var(--space);
    display: grid; grid-template-columns: 220px 1fr; gap: 3rem; align-items: start;
}
@media (max-width: 860px) { .policy-inner { grid-template-columns: 1fr; } }

/* ── ToC Sidebar ── */
.policy-toc {
    position: sticky; top: 110px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1.4rem;
    box-shadow: var(--shadow-sm);
}
.policy-toc__title {
    font-size: 0.7rem; letter-spacing: 0.2em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin: 0 0 0.8rem;
}
.policy-toc__list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.policy-toc__list a {
    display: block; color: var(--ink-muted); text-decoration: none;
    font-size: 0.85rem; padding: 0.4rem 0.65rem; border-radius: 8px;
    border-inline-start: 2px solid transparent;
    transition: all 0.2s;
}
.policy-toc__list a:hover {
    color: var(--gold); background: var(--accent-soft);
    border-inline-start-color: var(--gold);
}
@media (max-width: 860px) {
    .policy-toc { position: static; }
    .policy-toc__list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem; }
}
@media (max-width: 480px) { .policy-toc__list { grid-template-columns: 1fr; } }

/* ── Content Blocks ── */
.policy-block { margin-bottom: 2.8rem; scroll-margin-top: 110px; }
.policy-block:last-child { margin-bottom: 0; }
.policy-block__hdr {
    display: flex; align-items: center; gap: 0.9rem;
    margin-bottom: 1.1rem; padding-bottom: 0.9rem;
    border-bottom: 1px solid var(--border-subtle);
}
.policy-block__icon {
    width: 42px; height: 42px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.22);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.policy-block__title {
    font-family: var(--font-serif);
    font-size: 1.2rem; font-weight: 700; color: var(--ink); margin: 0;
}
.policy-text { color: var(--ink-muted); font-size: 0.96rem; line-height: 1.8; margin: 0 0 0.9rem; }
.policy-text:last-child { margin-bottom: 0; }

.policy-list { list-style: none; padding: 0; margin: 0 0 0.9rem; display: flex; flex-direction: column; gap: 0.6rem; }
.policy-list li {
    display: flex; align-items: flex-start; gap: 0.7rem;
    color: var(--ink-muted); font-size: 0.96rem; line-height: 1.65;
}
.policy-list li::before {
    content: '•'; color: var(--gold); font-size: 1.2rem;
    line-height: 1.1; flex-shrink: 0;
}

.policy-note {
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.25);
    border-inline-start: 3px solid var(--gold);
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin: 0.8rem 0;
    color: var(--ink-muted);
    font-size: 0.9rem; line-height: 1.7;
}
.policy-note strong { color: var(--gold); }

.policy-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.3);
    color: var(--gold);
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; padding: 0.28rem 0.75rem;
    border-radius: var(--radius-full); margin-bottom: 0.9rem;
    display: inline-flex;
}

/* ── CTA Box ── */
.policy-cta {
    background: linear-gradient(135deg, var(--bg-elevated), var(--bg-warm, #fdfaf5));
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 2.2rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.policy-cta h3 {
    font-family: var(--font-serif);
    font-size: 1.25rem; font-weight: 700; color: var(--ink); margin: 0 0 0.5rem;
}
.policy-cta p { color: var(--ink-muted); font-size: 0.9rem; margin: 0 0 1.5rem; line-height: 1.6; }
.policy-cta__btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.policy-cta__btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.8rem 1.5rem; border-radius: var(--radius-full);
    font-weight: 700; font-size: 0.88rem; text-decoration: none;
    transition: all 0.25s ease;
}
.policy-cta__btn--primary {
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508 !important;
    box-shadow: 0 4px 16px var(--gold-glow);
}
.policy-cta__btn--primary:hover { filter: brightness(1.08); transform: translateY(-2px); }
.policy-cta__btn--secondary {
    background: var(--bg); border: 1px solid var(--border); color: var(--ink-muted) !important;
}
.policy-cta__btn--secondary:hover { border-color: var(--gold); color: var(--gold) !important; }
</style>

<!-- Hero -->
<section class="policy-hero">
    <div class="container" style="position:relative;z-index:1;">
        <p class="policy-eyebrow"><span></span><?= $isAr ? 'سياساتنا' : 'Our Policies' ?><span></span></p>
        <h1><?= get_setting('policy_hero_title', $isAr ? 'سياسة الاستبدال والارتجاع' : 'Return & Exchange Policy') ?></h1>
        <p class="policy-hero__lead">
            <?= get_setting('policy_hero_lead', $isAr
                ? 'نريد أن تكون تجربتك ممتازة دائمًا. اقرأ سياستنا لتعرف حقوقك كاملة.'
                : "We want your experience to always be excellent. Read our policy to know your full rights.") ?>
        </p>
        <p class="policy-hero__updated"><?= get_setting('policy_hero_updated', $isAr ? 'آخر تحديث: مايو ٢٠٢٥' : 'Last updated: May 2025') ?></p>
    </div>
</section>

<!-- Content -->
<section class="policy-section">
    <div class="policy-inner">

        <!-- Sidebar ToC -->
        <aside class="policy-toc">
            <p class="policy-toc__title"><?= $isAr ? 'محتويات الصفحة' : 'On This Page' ?></p>
            <ul class="policy-toc__list">
                <li><a href="#exchange"><?= $isAr ? 'سياسة الاستبدال' : 'Exchange Policy' ?></a></li>
                <li><a href="#returns"><?= $isAr ? 'الاسترجاع' : 'Returns' ?></a></li>
                <li><a href="#shipping"><?= $isAr ? 'تكاليف الشحن' : 'Shipping Costs' ?></a></li>
                <li><a href="#contact"><?= $isAr ? 'تواصل معنا' : 'Contact Us' ?></a></li>
            </ul>
        </aside>

        <!-- Blocks -->
        <div>

            <div class="policy-block" id="exchange">
                <div class="policy-block__hdr">
                    <div class="policy-block__icon">🔄</div>
                    <h2 class="policy-block__title"><?= get_setting('policy_exchange_title', $isAr ? 'سياسة الاستبدال' : 'Exchange Policy') ?></h2>
                </div>
                <span class="policy-badge">⏱ <?= get_setting('policy_exchange_badge', $isAr ? 'خلال ٧ أيام' : 'Within 7 days') ?></span>
                <p class="policy-text">
                    <?= get_setting('policy_exchange_text', $isAr
                        ? 'نوفر إمكانية استبدال المنتجات خلال <strong style="color:var(--gold);">٧ أيام</strong> من تاريخ الاستلام وفق الشروط الموضحة أدناه.'
                        : 'We offer product exchange within <strong style="color:var(--gold);">7 days</strong> of receipt, under the conditions outlined below.') ?>
                </p>
                <ul class="policy-list">
                    <?php
                    $exList = get_setting('policy_exchange_list_' . current_lang(), $isAr
                        ? "المنتج غير مستخدم وفي عبوته الأصلية المغلقة\nالاستبدال بمنتج بنفس القيمة أو أعلى (مع تسوية الفرق)\nتقديم فاتورة الشراء الأصلية أو رقم الطلب\nالتواصل مع فريق الدعم أولاً قبل إرسال المنتج"
                        : "Product unused and in its original sealed packaging\nExchange for equal or higher value product (difference adjusted)\nPresent original purchase invoice or order number\nContact support team first before sending the product");
                    $exLines = array_filter(array_map('trim', explode("\n", $exList)), fn($v) => $v !== '');
                    foreach ($exLines as $li): ?>
                    <li><?= esc($li) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="policy-note">
                    <strong><?= $isAr ? 'ملاحظة:' : 'Note:' ?></strong>
                    <?= get_setting('policy_exchange_note', $isAr ? ' يُحسب الوقت من تاريخ التسليم الفعلي لا من تاريخ الطلب.' : ' The period is calculated from the actual delivery date, not the order date.') ?>
                </div>
            </div>

            <div class="policy-block" id="returns">
                <div class="policy-block__hdr">
                    <div class="policy-block__icon">↩️</div>
                    <h2 class="policy-block__title"><?= get_setting('policy_returns_title', $isAr ? 'الاسترجاع والإرجاع' : 'Returns & Refunds') ?></h2>
                </div>
                <p class="policy-text"><?= get_setting('policy_returns_lead', $isAr ? 'نقبل طلبات الإرجاع في الحالات التالية فقط:' : 'We accept return requests only in the following cases:') ?></p>
                <ul class="policy-list">
                    <?php
                    $retList = get_setting('policy_returns_list_' . current_lang(), $isAr
                        ? "استلام منتج تالف أو مكسور بسبب الشحن\nاستلام منتج مختلف عن المطلوب (خطأ من جانبنا)\nوجود عيب في المنتج لم يُذكر في الوصف"
                        : "Receiving a damaged or broken product due to shipping\nReceiving a different product than ordered (error on our part)\nA product defect not mentioned in the description");
                    $retLines = array_filter(array_map('trim', explode("\n", $retList)), fn($v) => $v !== '');
                    foreach ($retLines as $li): ?>
                    <li><?= esc($li) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="policy-note">
                    <strong><?= $isAr ? 'في هذه الحالات:' : 'In these cases:' ?></strong>
                    <?= get_setting('policy_returns_note', $isAr
                        ? ' يُرجى التواصل فوراً وإرسال صور للمنتج لمراجعة الطلب واتخاذ الإجراء المناسب.'
                        : ' Please contact us immediately and send photos of the product to review and take appropriate action.') ?>
                </div>
            </div>

            <div class="policy-block" id="shipping">
                <div class="policy-block__hdr">
                    <div class="policy-block__icon">🚚</div>
                    <h2 class="policy-block__title"><?= get_setting('policy_shipping_title', $isAr ? 'تكاليف شحن الاستبدال' : 'Exchange Shipping Costs') ?></h2>
                </div>
                <ul class="policy-list">
                    <?php
                    $shpList = get_setting('policy_shipping_list_' . current_lang(), $isAr
                        ? "خطأ من جانبنا (منتج خاطئ أو تالف): نتحمل تكاليف الشحن كاملة\nاستبدال لأسباب شخصية: يتحمل العميل تكاليف شحن الإرجاع\nشحن المنتج البديل: مجاني في جميع حالات الاستبدال المقبولة"
                        : "Error on our part (wrong or damaged): we cover full shipping cost\nExchange for personal reasons: customer covers return shipping\nReplacement product shipping: free in all accepted exchange cases");
                    $shpLines = array_filter(array_map('trim', explode("\n", $shpList)), fn($v) => $v !== '');
                    foreach ($shpLines as $li): ?>
                    <li><?= esc($li) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="policy-block" id="contact">
                <div class="policy-cta">
                    <h3><?= get_setting('policy_contact_title', $isAr ? 'هل لديك سؤال عن سياستنا؟' : 'Have a Question About Our Policy?') ?></h3>
                    <p>
                        <?= get_setting('policy_contact_text', $isAr
                            ? 'فريقنا جاهز للمساعدة. تواصل معنا عبر واتساب أو نموذج التواصل.'
                            : "Our team is ready to help. Contact us via WhatsApp or our contact form.") ?>
                    </p>
                    <div class="policy-cta__btns">
                        <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" class="policy-cta__btn policy-cta__btn--primary">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24z"/></svg>
                            WhatsApp
                        </a>
                        <a href="<?= esc(url('contact.php')) ?>" class="policy-cta__btn policy-cta__btn--secondary">
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
