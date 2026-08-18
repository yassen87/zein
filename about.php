<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';

$pageTitle = t('page_story');
$pageDescription = get_page_description('about');
$canonicalUrl = get_current_url_without_lang();
$isAr = current_lang() === 'ar';

require __DIR__ . '/includes/header.php';
?>

<style>
/* ── About Page ── */
.about-hero {
    padding: clamp(3rem, 7vw, 5.5rem) 0 clamp(2.5rem, 5vw, 4rem);
    text-align: center;
    background: linear-gradient(180deg, var(--bg-elevated) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.about-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, var(--gold-glow), transparent 70%);
    pointer-events: none;
}
.about-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.72rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
}
.about-eyebrow span { width: 28px; height: 1px; background: var(--gold); display: inline-block; }
.about-hero h1 {
    font-family: var(--font-serif);
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 700;
    margin: 0 0 1rem;
    color: var(--ink);
    line-height: 1.1;
}
.about-hero h1 em {
    font-style: normal;
    background: linear-gradient(120deg, var(--gold-bright), var(--gold));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.about-hero__lead {
    color: var(--ink-muted);
    font-size: 1.05rem;
    max-width: 560px;
    margin: 0 auto 2rem;
    line-height: 1.7;
}

/* Stats row */
.about-stats {
    display: flex;
    justify-content: center;
    gap: clamp(1.5rem, 4vw, 3.5rem);
    flex-wrap: wrap;
    margin-top: 0.5rem;
}
.about-stat { text-align: center; }
.about-stat__num {
    font-family: var(--font-serif);
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 700;
    color: var(--gold);
    display: block;
    line-height: 1;
}
.about-stat__lbl {
    font-size: 0.75rem;
    color: var(--ink-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    display: block;
    margin-top: 0.25rem;
}

/* ── Story Section ── */
.about-story {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.about-story__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3.5rem;
    align-items: center;
}
@media (max-width: 800px) { .about-story__grid { grid-template-columns: 1fr; gap: 2.5rem; } }

.about-story__visual {
    border-radius: var(--radius-lg);
    aspect-ratio: 4/5;
    background: linear-gradient(145deg, var(--bg-warm, #f8f5ef) 0%, var(--bg-elevated) 100%);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 1rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}
.about-story__visual::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 30% 20%, var(--gold-glow), transparent 60%);
}
.about-story__visual-icon { font-size: 5rem; position: relative; z-index: 1; }
.about-story__visual-name {
    font-family: var(--font-serif);
    font-size: 1.15rem;
    color: var(--gold);
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    position: relative;
    z-index: 1;
}
.about-story__badge {
    position: absolute;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.35);
    color: var(--gold);
    padding: 0.5rem 1.3rem;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    white-space: nowrap;
    z-index: 2;
}
.about-section-lbl {
    font-size: 0.72rem; letter-spacing: 0.22em; text-transform: uppercase;
    color: var(--gold); font-weight: 700; margin-bottom: 0.6rem;
}
.about-section-title {
    font-family: var(--font-serif);
    font-size: clamp(1.5rem, 2.8vw, 2.2rem);
    font-weight: 700; color: var(--ink);
    margin: 0 0 1.2rem; line-height: 1.25;
}
.about-section-title em { font-style: normal; color: var(--gold); }
.about-divider {
    width: 44px; height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    border: none; margin: 1.2rem 0;
}
html[dir="rtl"] .about-divider { background: linear-gradient(270deg, var(--gold), transparent); }
.about-text {
    color: var(--ink-muted);
    font-size: 0.97rem; line-height: 1.8; margin: 0 0 0.9rem;
}

/* ── Values ── */
.about-values {
    background: var(--bg-elevated);
    padding: clamp(3rem, 6vw, 5rem) 0;
    border-top: 1px solid var(--border-subtle);
    border-bottom: 1px solid var(--border-subtle);
}
.about-values__header { text-align: center; margin-bottom: 3rem; }
.about-values__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
@media (max-width: 768px) { .about-values__grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .about-values__grid { grid-template-columns: 1fr; } }

.about-val-card {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1.6rem 1.4rem;
    transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}
.about-val-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    opacity: 0; transition: opacity 0.3s;
}
.about-val-card:hover {
    border-color: var(--gold);
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}
.about-val-card:hover::before { opacity: 1; }
.about-val-card__icon {
    width: 46px; height: 46px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.2);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; margin-bottom: 1rem;
}
.about-val-card__title { font-size: 1rem; font-weight: 700; color: var(--ink); margin: 0 0 0.5rem; }
.about-val-card__text { font-size: 0.88rem; color: var(--ink-muted); line-height: 1.7; margin: 0; }

/* ── Promise CTA ── */
.about-promise {
    background: var(--bg);
    padding: clamp(3rem, 6vw, 5rem) 0;
}
.about-promise__box {
    background: linear-gradient(135deg, var(--bg-elevated) 0%, var(--bg-warm, #fdfaf5) 100%);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: clamp(2rem, 4vw, 3.5rem);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}
.about-promise__box::after {
    content: '✦';
    position: absolute;
    top: -1rem; right: 1.5rem;
    font-size: 8rem;
    color: var(--gold-glow);
    pointer-events: none;
}
@media (max-width: 700px) { .about-promise__box { grid-template-columns: 1fr; padding: 1.8rem; } }

.about-promise__cta {
    display: inline-flex; align-items: center; gap: 0.6rem;
    background: linear-gradient(145deg, var(--gold-bright), var(--gold) 50%, var(--gold-dim));
    color: #1a1508 !important;
    font-weight: 800; font-size: 0.92rem;
    text-decoration: none;
    padding: 0.85rem 1.8rem;
    border-radius: var(--radius-full);
    transition: all 0.3s ease;
    margin-top: 1.5rem;
    box-shadow: 0 4px 20px var(--gold-glow);
}
.about-promise__cta:hover { filter: brightness(1.08); transform: translateY(-2px); }

.about-promise__list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.9rem; }
.about-promise__list li {
    display: flex; align-items: center; gap: 0.75rem;
    color: var(--ink-muted); font-size: 0.93rem;
}
.about-promise__list li::before {
    content: '✓';
    width: 24px; height: 24px;
    background: var(--accent-soft);
    border: 1px solid rgba(212,175,55,0.3);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); font-weight: 700; font-size: 0.75rem;
    flex-shrink: 0;
}
</style>

<!-- Hero -->
<section class="about-hero">
    <div class="container" style="position:relative;z-index:1;">
        <p class="about-eyebrow">
            <span></span><?= $isAr ? 'من نحن' : 'About Us' ?><span></span>
        </p>
        <h1><?= get_setting('about_hero_title', $isAr ? 'عطورٌ <em>تحكي قصة</em>' : 'Fragrances That <em>Tell a Story</em>') ?></h1>
        <p class="about-hero__lead">
            <?= get_setting('about_hero_lead', $isAr
                ? 'منذ تأسيسنا، ونحن نؤمن بأن العطر ليس مجرد رائحة — بل هو ذكرى تُصنع، وشخصية تُعبّر.'
                : "Since our founding, we believe fragrance isn't just a scent — it's a memory in the making, a personality expressed.") ?>
        </p>
        <div class="about-stats">
            <div class="about-stat"><span class="about-stat__num">+500</span><span class="about-stat__lbl"><?= $isAr ? 'عطر أصيل' : 'Original Scents' ?></span></div>
            <div class="about-stat"><span class="about-stat__num">2018</span><span class="about-stat__lbl"><?= $isAr ? 'سنة التأسيس' : 'Year Founded' ?></span></div>
            <div class="about-stat"><span class="about-stat__num">+10K</span><span class="about-stat__lbl"><?= $isAr ? 'عميل سعيد' : 'Happy Clients' ?></span></div>
            <div class="about-stat"><span class="about-stat__num">24H</span><span class="about-stat__lbl"><?= $isAr ? 'توصيل سريع' : 'Fast Delivery' ?></span></div>
        </div>
    </div>
</section>

<!-- Story -->
<section class="about-story">
    <div class="container">
        <div class="about-story__grid">
            <div class="about-story__visual">
                <?php
                $storyImg = get_setting('about_story_image', '');
                if ($storyImg !== ''): ?>
                    <img src="<?= esc(base_url('assets/uploads/' . $storyImg)) ?>" alt="<?= $isAr ? 'زين للعطور' : 'Zain Perfumes' ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                    <div class="about-story__badge" style="position:absolute;bottom:1.5rem;left:50%;transform:translateX(-50%);z-index:2;"><?= $isAr ? 'منذ ٢٠١٨' : 'Est. 2018' ?></div>
                <?php else: ?>
                    <div class="about-story__visual-icon">🌸</div>
                    <div class="about-story__visual-name"><?= $isAr ? 'زين للعطور' : 'Zain Perfumes' ?></div>
                    <div class="about-story__badge"><?= $isAr ? 'منذ ٢٠١٨' : 'Est. 2018' ?></div>
                <?php endif; ?>
            </div>
            <div>
                <p class="about-section-lbl"><?= $isAr ? 'حكايتنا' : 'Our Story' ?></p>
                <h2 class="about-section-title">
                    <?= get_setting('about_story_title', $isAr
                        ? 'من شغفٍ صغير إلى <em>دار عطور</em> معروفة'
                        : 'From a Small Passion to a <em>Known Fragrance House</em>') ?>
                </h2>
                <hr class="about-divider">
                <p class="about-text">
                    <?= get_setting('about_story_p1', $isAr
                        ? 'بدأت زين للعطور من شغف حقيقي بعالم العطور وفن التركيب، إيمانًا بأن لكل شخص عطره الذي يعبّر عن هويته ويترك أثرًا جميلًا في كل مكان يمر به.'
                        : "Zain Perfumes started from a genuine passion for the world of fragrance and the art of composition, with the belief that every person has a scent that expresses their identity and leaves a beautiful impression wherever they go.") ?>
                </p>
                <p class="about-text">
                    <?= get_setting('about_story_p2', $isAr
                        ? 'اليوم نقدم أكثر من 500 عطر أصيل بأسعار تنافسية، بدءًا من العطور الخليجية الأصيلة والعود الفاخر، وصولًا إلى التوقيعات العالمية والعطور الحصرية. كل قطعة مختارة بعناية لتناسب أذواق مختلفة.'
                        : "Today we offer over 500 original fragrances at competitive prices, from authentic Gulf perfumes and luxury oud to global signatures and exclusive scents. Every piece carefully selected to suit different tastes.") ?>
                </p>
                <p class="about-text">
                    <?= get_setting('about_story_p3', $isAr
                        ? 'نؤمن بأن جودة العطر لا يجب أن تكون حكرًا على سعر باهظ، لذلك نسعى دائمًا لتقديم أفضل تجربة بأسعار عادلة وخدمة من القلب.'
                        : "We believe quality fragrance shouldn't be limited to a high price tag, which is why we always strive to deliver the best experience at fair prices with service from the heart.") ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="about-values">
    <div class="container">
        <div class="about-values__header">
            <p class="about-section-lbl"><?= $isAr ? 'ما يميّزنا' : 'What Sets Us Apart' ?></p>
            <h2 class="about-section-title" style="margin-bottom:0.4rem;">
                <?= get_setting('about_values_title', $isAr ? 'قيمنا <em>وفلسفتنا</em>' : 'Our Values <em>&amp; Philosophy</em>') ?>
            </h2>
            <p style="color:var(--ink-muted);font-size:0.92rem;margin:0;">
                <?= get_setting('about_values_lead', $isAr ? 'المبادئ التي تقود كل قرار نتخذه.' : 'The principles that guide every decision we make.') ?>
            </p>
        </div>
        <div class="about-values__grid">
            <?php
            $valsRaw = get_setting('about_values_' . current_lang(), $isAr
                ? "💎|الجودة أولاً|نختار كل عطر بعناية ونضمن أصالة كل منتج نقدمه.\n🌟|تجربة فريدة|نسعى لأن يكون كل تفاعل مع زين للعطور تجربة ممتعة.\n🤝|خدمة من القلب|فريقنا دائمًا متاح عبر واتساب لتقديم أفضل توصية.\n🚀|شحن سريع وآمن|تغليف احترافي يضمن وصول طلبك بأمان وسرعة.\n💰|أسعار تنافسية|الفخامة في متناول الجميع — بلا مساومة على الجودة.\n🔄|سياسة مرنة|سياسة استبدال عادلة وواضحة لراحتك الكاملة."
                : "💎|Quality First|We carefully select every fragrance and guarantee the authenticity of every product.\n🌟|Unique Experience|We strive to make every interaction with Zain Perfumes a delightful experience.\n🤝|Service from the Heart|Our team is always on WhatsApp to give you the best recommendation.\n🚀|Fast & Safe Shipping|Professional packaging ensures your order arrives safely and quickly.\n💰|Competitive Prices|Luxury accessible to everyone — without compromising on quality.\n🔄|Flexible Policy|A fair and clear exchange policy to ensure your full peace of mind.");
            $valLines = array_filter(array_map('trim', explode("\n", $valsRaw)), fn($v) => $v !== '');
            foreach ($valLines as $line):
                $parts = explode('|', $line, 3);
                $icon  = trim($parts[0] ?? '');
                $title = trim($parts[1] ?? '');
                $text  = trim($parts[2] ?? '');
            ?>
            <div class="about-val-card">
                <div class="about-val-card__icon"><?= $icon ?></div>
                <h3 class="about-val-card__title"><?= esc($title) ?></h3>
                <p class="about-val-card__text"><?= esc($text) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promise CTA -->
<section class="about-promise">
    <div class="container">
        <div class="about-promise__box">
            <div>
                <p class="about-section-lbl"><?= $isAr ? 'وعدنا لك' : 'Our Promise to You' ?></p>
                <h2 class="about-section-title">
                    <?= get_setting('about_promise_title', $isAr ? 'تجربة شراء <em>لا تُنسى</em>' : 'An <em>Unforgettable</em> Shopping Experience') ?>
                </h2>
                <p class="about-text" style="margin-bottom:0;">
                    <?= get_setting('about_promise_text', $isAr
                        ? 'كل طلب هو فرصة لنثبت التزامنا بالجودة وخدمة العملاء. تسوّق بثقة — نحن معك في كل خطوة.'
                        : "Every order is an opportunity to prove our commitment to quality and service. Shop with confidence — we're with you every step.") ?>
                </p>
                <a href="<?= esc(url('contact.php')) ?>" class="about-promise__cta">
                    <?= $isAr ? 'تواصل معنا' : 'Get in Touch' ?>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <?= $isAr ? '<path d="M15 18l-6-6 6-6"/>' : '<path d="M9 18l6-6-6-6"/>' ?>
                    </svg>
                </a>
            </div>
            <ul class="about-promise__list">
                <?php
                $promisesRaw = get_setting('about_promises_' . current_lang(), $isAr
                    ? "أكثر من ٥٠٠ عطر أصيل ومتنوع\nضمان أصالة المنتجات ١٠٠٪\nشحن سريع خلال ٢٤–٧٢ ساعة\nدعم واتساب طوال أيام الأسبوع\nسياسة استبدال عادلة وواضحة\nتغليف احترافي يحافظ على العطر"
                    : "Over 500 authentic & diverse fragrances\n100% product authenticity guarantee\nFast shipping within 24–72 hours\nWhatsApp support all week long\nFair and clear exchange policy\nProfessional packaging to protect your order");
                $promiseLines = array_filter(array_map('trim', explode("\n", $promisesRaw)), fn($v) => $v !== '');
                foreach ($promiseLines as $p): ?>
                <li><?= esc($p) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
