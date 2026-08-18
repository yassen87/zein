<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$pageTitle = t('page_home');
$pageDescription = get_page_description('home');
$canonicalUrl = get_current_url_without_lang();

$pdo        = medal_pdo();
$categories = [];
$brands     = [];

if ($pdo) {
    try {
        if (!isset($_SESSION['_migrated_categories_image'])) {
            try { $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS image VARCHAR(500) DEFAULT ''"); }
            catch (Throwable $e) {
                error_log('Error in index.php ALTER TABLE categories: ' . $e->getMessage());
            }
            $_SESSION['_migrated_categories_image'] = true;
        }

        $categories = $pdo->query(
            "SELECT * FROM categories WHERE slug NOT IN ('gifts','gift','hadiya') ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
        $brands = $pdo->query(
            "SELECT * FROM brands ORDER BY is_popular DESC, sort_order ASC, id ASC LIMIT 12"
        )->fetchAll();
    } catch (Throwable $e) {
    error_log('Error in index.php categories/brands query: ' . $e->getMessage());
}
}

// Load all products once for category sections (avoids N+1 queries)
$allProducts = get_products_localized();

// ── Data ────────────────────────────────────────────────────────────────────
$offerBundles   = get_offer_bundles();
$bestsellers    = get_bestsellers_localized(8);
$latestProducts = get_latest_products(3);

$extraCss = [
    url('assets/css/pages/home.css?v=' . filemtime(__DIR__ . '/assets/css/pages/home.css'))
];

require __DIR__ . '/includes/header.php';

// ── Helpers ─────────────────────────────────────────────────────────────────
function hp_section_header(string $title, string $subtitle, string $viewAllUrl, string $viewAllLabel, string $bg = '#fff'): void {
    echo '<section class="hp-section" style="background:' . $bg . ';">';
    echo '<div class="hp-container">';
    echo '<div class="hp-section-head">';
    echo '<div class="hp-section-head__left">';
    echo '<h2 class="hp-section-title">' . $title . '</h2>';
    if ($subtitle) echo '<p class="hp-section-sub">' . htmlspecialchars($subtitle, ENT_QUOTES) . '</p>';
    echo '</div>';
    if ($viewAllUrl) {
        echo '<a href="' . htmlspecialchars($viewAllUrl, ENT_QUOTES) . '" class="hp-view-all">';
        echo htmlspecialchars($viewAllLabel, ENT_QUOTES) . ' <span>›</span>';
        echo '</a>';
    }
    echo '</div>';
}

function hp_products_grid(array $products, string $sectionId, bool $isAr, ?array $offerPrices = null): void {
    $total = count($products);
    if ($total === 0) { echo '</div></section>'; return; }
    echo '<div class="hp-grid" id="grid-' . htmlspecialchars($sectionId, ENT_QUOTES) . '">';
    foreach ($products as $i => $p):
        $extraClass  = $i >= 4 ? ' hp-extra' : '';
        $showBestseller = true;
        $offerPrice  = $offerPrices[$p['id']] ?? null;
    ?>
        <div class="hp-card-wrap<?= $extraClass ?>">
            <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
        </div>
    <?php endforeach;
    echo '</div>';

    if ($total > 4): ?>
    <div class="hp-show-more-wrap">
        <button class="hp-show-more-btn"
            data-grid="grid-<?= htmlspecialchars($sectionId, ENT_QUOTES) ?>"
            data-label-more="<?= $isAr ? 'عرض المزيد' : 'Show More' ?>"
            data-label-less="<?= $isAr ? 'عرض أقل' : 'Show Less' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            <?= $isAr ? 'عرض المزيد' : 'Show More' ?>
        </button>
    </div>
    <?php endif;
    echo '</div></section>';
}
?>

<?php /* ─── Toast ─── */ ?>



<?php /* ─── Mobile Home Search Bar ─── */ ?>
<div class="hp-search-mobile search-box-container">
    <div class="hp-search-mobile__inner">
        <form action="<?= esc(url('products.php')) ?>" method="GET" class="header-search">
            <input type="search" name="q" class="header-search__input" placeholder="<?= esc(t('search_placeholder')) ?>" autocomplete="off">
            <button type="button" class="header-search__clear" aria-label="<?= $isAr ? 'مسح البحث' : 'Clear search' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <button type="submit" class="header-search__btn" aria-label="<?= $isAr ? 'بحث' : 'Search' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
        </form>
    </div>
    <div class="header-search__dropdown hp-search-dropdown-panel" style="display:none;">
        <div class="search-results"></div>
        <a href="" class="search-view-all"><?= t('view_all_results') ?> →</a>
    </div>
</div>

<?php /* ─── Hero Slider ─── */ ?>
<?php
$heroImages = [];
$heroLinks = [];
for ($i = 1; $i <= 3; $i++) {
    $img = get_setting('hero_image_' . $i);
    if ($img) $heroImages[] = url('assets/uploads/' . $img);
    $heroLinks[$i] = get_setting('hero_link_' . $i);
}
$heroTitle = get_setting('hero_title') ?: ($isAr ? 'اكتشف أفخر العطور' : 'Discover Luxury Fragrances');
$heroSubtitle = get_setting('hero_subtitle') ?: ($isAr ? 'عطور عربية وفرنسية فاخرة' : 'Premium Arabic & French Perfumes');
$heroCtaText = get_setting('hero_cta_text') ?: ($isAr ? 'تسوق الآن' : 'Shop Now');
$heroCtaLink = get_setting('hero_cta_link') ?: 'products.php';
?>
<div class="hero-slider" id="hero-slider">
    <?php foreach ($heroImages as $index => $imgUrl): 
        $link = $heroLinks[$index + 1] ?? '';
        $hasLink = $link !== '';
    ?>
        <div class="hero-slide<?= $index === 0 ? ' is-active' : '' ?>" style="background-image:url('<?= esc($imgUrl) ?>');">
            <?php if ($hasLink): ?><a href="<?= esc(url($link)) ?>" class="hero-slide-link"></a><?php endif; ?>
            <?php if ($index === 0): ?>
                <div class="hero-overlay">
                    <h1 class="hero-overlay__title"><?= esc($heroTitle) ?></h1>
                    <p class="hero-overlay__sub"><?= esc($heroSubtitle) ?></p>
                    <a href="<?= esc(url($heroCtaLink)) ?>" class="hero-overlay__cta"><?= esc($heroCtaText) ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (count($heroImages) > 1): ?>
        <div class="hero-slider-dots">
            <?php foreach ($heroImages as $index => $imgUrl): ?>
                <span class="hero-dot<?= $index === 0 ? ' is-active' : '' ?>"></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php if (count($heroImages) > 1): ?>
<script>
(function(){
    var slides=document.querySelectorAll('#hero-slider .hero-slide');
    var dots=document.querySelectorAll('#hero-slider .hero-dot');
    if(slides.length<=1)return;
    var current=0;
    var total=slides.length;
    var autoTimer;
    var pauseTimer;

    function goTo(index) {
        slides[current].classList.remove('is-active');
        if(dots.length) dots[current].classList.remove('is-active');
        current = ((index % total) + total) % total;
        slides[current].classList.add('is-active');
        if(dots.length) dots[current].classList.add('is-active');
    }

    function next() {
        goTo(current + 1);
    }

    function prev() {
        goTo(current - 1);
    }

    function startAuto() {
        stopAuto();
        autoTimer = setInterval(next, 5000);
    }

    function stopAuto() {
        clearInterval(autoTimer);
        clearTimeout(pauseTimer);
    }

    function pauseAuto() {
        stopAuto();
        pauseTimer = setTimeout(startAuto, 8000);
    }

    // Click on dots
    dots.forEach(function(dot, i) {
        dot.addEventListener('click', function() {
            goTo(i);
            pauseAuto();
        });
    });

    // Add arrow buttons
    var slider = document.getElementById('hero-slider');
    var leftArrow = document.createElement('button');
    leftArrow.className = 'hero-slider-arrow hero-slider-arrow--left';
    leftArrow.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>';
    leftArrow.setAttribute('aria-label', 'التالي');
    leftArrow.addEventListener('click', function() { next(); pauseAuto(); });

    var rightArrow = document.createElement('button');
    rightArrow.className = 'hero-slider-arrow hero-slider-arrow--right';
    rightArrow.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>';
    rightArrow.setAttribute('aria-label', 'السابق');
    rightArrow.addEventListener('click', function() { prev(); pauseAuto(); });

    slider.appendChild(leftArrow);
    slider.appendChild(rightArrow);

    startAuto();
})();
</script>
<?php endif; ?>

<?php
$bgAlt = ['#fff', '#fafafa'];
$bgIdx = 0;
?>

<?php /* ─── Category Navigation Circles ─── */ ?>
<?php if (!empty($categories)): ?>
<nav class="hp-cats-bar" aria-label="<?= $isAr ? 'الأقسام' : 'Categories' ?>">
    <div class="hp-container">
        <div class="hp-slider-container">
            <button class="hp-slider-arrow hp-slider-arrow--left" onclick="scrollGrid('grid-categories', -1)" aria-label="Scroll Left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="hp-cats-bar__inner" id="grid-categories">
                <?php foreach ($categories as $c):
                    $cName = $isAr ? (string)$c['name_ar'] : (string)$c['name_en'];
                    $cImg  = (string)($c['image'] ?? '');
                    $cUrl  = $cImg !== '' ? base_url('assets/uploads/' . $cImg) : '';
                ?>
                <a href="<?= esc(url('products.php?cat=' . $c['slug'])) ?>" class="hp-cat-pill">
                    <div class="hp-cat-pill__circle">
                        <?php if ($cUrl !== ''): ?>
                            <img src="<?= esc($cUrl) ?>" alt="<?= esc($cName) ?>" class="hp-cat-pill__img" loading="lazy" width="72" height="72">
                        <?php else: ?>
                            <span class="hp-cat-pill__emoji"><?= esc(get_home_category_emoji((string)$c['slug'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hp-cat-pill__text">
                        <span class="hp-cat-pill__label"><?= esc($cName) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="hp-slider-arrow hp-slider-arrow--right" onclick="scrollGrid('grid-categories', 1)" aria-label="Scroll Right">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</nav>
<?php endif; ?>

<?php /* ══════════════════════════════════════════
   1. EXCLUSIVE OFFERS SECTION
══════════════════════════════════════════ */ ?>
<?php if (!empty($offerBundles)):
    $bg = $bgAlt[$bgIdx % 2];
    $bgIdx++;
?>
<section class="hp-section hp-offer-bundle-section" style="background:<?= $bg ?>;">
    <div class="hp-container">
        <div class="hp-section-head">
            <div class="hp-section-head__left">
                <div class="hp-bundle-label-row" style="margin-bottom:0.2rem;">
                    <span class="hp-offers-tag">🏷️ <?= $isAr ? 'العروض الحصرية' : 'Exclusive Offers' ?></span>
                </div>
                <h2 class="hp-section-title"><?= $isAr ? 'باقات وعروض التوفير' : 'Savings Bundles & Offers' ?></h2>
                <p class="hp-section-sub"><?= $isAr ? 'وفر أكثر مع باقاتنا المجمعة بأسعار حصرية' : 'Save more with our bundled packages at exclusive prices' ?></p>
            </div>
            <?php if (has_any_offers()): ?>
            <a href="<?= esc(url('offers.php')) ?>" class="hp-view-all">
                <?= $isAr ? 'كل العروض' : 'All Offers' ?> <span>›</span>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="hp-slider-container">
            <button class="hp-slider-arrow hp-slider-arrow--left" onclick="scrollGrid('grid-offers', -1)" aria-label="Scroll Left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="hp-grid" id="grid-offers">
                <?php foreach ($offerBundles as $i => $bundle): ?>
                <div class="hp-card-wrap">
                    <article class="product-card" data-bundle-id="<?= (int)$bundle['id'] ?>">
                        <div class="product-card__inner">
                            <!-- Badges -->
                            <div class="product-card__media">
                                <?php if ($bundle['compare_at_price'] > $bundle['price']): ?>
                                    <div class="product-card__badge product-card__badge--discount">
                                        <?php if ($bundle['discount_type'] === 'percent'): ?>
                                            -<?= number_format($bundle['discount_value'], 0) ?>%
                                        <?php else: ?>
                                            <?= $isAr ? 'خصم خاص' : 'Discount' ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-card__badge product-card__badge--new" style="background:#c5a059; color:#fff; inset-inline-start: auto; inset-inline-end: 10px;">
                                    <?= (int)$bundle['quantity'] ?> <?= $isAr ? 'قطع' : 'Pieces' ?>
                                </div>

                                <!-- Bundle Image -->
                                <div class="product-card__image-wrap">
                                    <div class="<?= esc(product_image_class($bundle['image'], 'product-card__image')) ?>"
                                         role="img"
                                         aria-label="<?= esc($bundle['name']) ?>"
                                         <?= product_image_style($bundle['image']) ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="product-card__content">
                                <h3 class="product-card__title"><?= esc($bundle['name']) ?></h3>
                                
                                <?php if ($bundle['variant_label']): ?>
                                    <div class="product-card__variant-hint">
                                        <?= esc($bundle['variant_label']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Price -->
                                <div class="product-card__price-row" style="display:flex; align-items:center; gap:0.5rem; justify-content:center;">
                                    <span class="product-card__price">
                                        <?= esc(format_price($bundle['price'])) ?>
                                    </span>
                                    <?php if ($bundle['compare_at_price'] > $bundle['price']): ?>
                                        <span class="product-card__compare" style="text-decoration:line-through; font-size:0.85rem; color:#aaa; font-weight:normal;">
                                            <?= esc(format_price($bundle['compare_at_price'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Add to Cart Footer -->
                            <div class="product-card__footer">
                                <button type="button"
                                        class="btn-quick-add hp-quick-add-bundle"
                                        data-bundle-id="<?= (int)$bundle['id'] ?>">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                    <span><?= $isAr ? 'أضف للسلة' : 'Add to Cart' ?></span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="hp-slider-arrow hp-slider-arrow--right" onclick="scrollGrid('grid-offers', 1)" aria-label="Scroll Right">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>


<?php /* ══════════════════════════════════════════
   2. BESTSELLERS SECTION
══════════════════════════════════════════ */ ?>
<?php if (!empty($bestsellers)):
    $bg    = $bgAlt[$bgIdx % 2];
    $bgIdx++;
?>
<section class="hp-section" style="background:<?= $bg ?>;">
    <div class="hp-container">
        <div class="hp-section-head">
            <div class="hp-section-head__left">
                <h2 class="hp-section-title">🏆 <?= $isAr ? 'الأكثر مبيعاً' : 'Best Sellers' ?></h2>
                <p class="hp-section-sub"><?= $isAr ? 'العطور الأكثر طلباً من عملائنا المميزين' : 'The most wanted fragrances by our valued customers' ?></p>
            </div>
            <a href="<?= esc(url('products.php')) ?>" class="hp-view-all"><?= $isAr ? 'عرض الكل' : 'View All' ?> <span>›</span></a>
        </div>
        <div class="hp-slider-container">
            <button class="hp-slider-arrow hp-slider-arrow--left" onclick="scrollGrid('grid-bestsellers', -1)" aria-label="Scroll Left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="hp-grid" id="grid-bestsellers">
                <?php foreach ($bestsellers as $i => $p):
                    $showBestseller = true;
                    $offerPrice     = null;
                ?>
                <div class="hp-card-wrap">
                    <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="hp-slider-arrow hp-slider-arrow--right" onclick="scrollGrid('grid-bestsellers', 1)" aria-label="Scroll Right">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>


<?php /* ══════════════════════════════════════════
   3. CATEGORY SECTIONS
══════════════════════════════════════════ */ ?>
<?php foreach ($categories as $cat):
    $slug    = $cat['slug'];
    $prods   = array_values(array_filter($allProducts, function($p) use ($slug) {
        return in_array($slug, $p['categories'] ?? [$p['category']], true);
    }));
    if (empty($prods)) continue;
    $catName = $isAr ? $cat['name_ar'] : $cat['name_en'];
    $bg      = $bgAlt[$bgIdx % 2];
    $bgIdx++;
    $sid     = 'cat-' . $slug;
?>
<section class="hp-section hp-cat-section" style="background:<?= $bg ?>;" id="sec-<?= esc($slug) ?>">
    <div class="hp-container">
        <!-- Category text header (no image) -->
        <div class="hp-section-head">
            <div class="hp-section-head__left">
                <h2 class="hp-section-title"><?= esc($catName) ?></h2>
            </div>
            <a href="<?= esc(url('products.php?cat=' . $slug)) ?>" class="hp-view-all"><?= $isAr ? 'عرض الكل' : 'View All' ?> <span>›</span></a>
        </div>

        <div class="hp-slider-container">
            <button class="hp-slider-arrow hp-slider-arrow--left" onclick="scrollGrid('grid-<?= esc($sid) ?>', -1)" aria-label="Scroll Left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="hp-grid" id="grid-<?= esc($sid) ?>">
                <?php foreach ($prods as $i => $p):
                    $showBestseller = true;
                    $offerPrice     = null;
                ?>
                <div class="hp-card-wrap">
                    <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="hp-slider-arrow hp-slider-arrow--right" onclick="scrollGrid('grid-<?= esc($sid) ?>', 1)" aria-label="Scroll Right">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</section>
<?php endforeach; ?>


<?php /* ══════════════════════════════════════════
   4. BRAND PERFUMES SECTION (Moved after categories)
══════════════════════════════════════════ */ ?>
<?php
$brandProducts = get_brand_products_localized(8);
if (!empty($brandProducts)):
    $bg = $bgAlt[$bgIdx % 2];
    $bgIdx++;
?>
<section class="hp-section" style="background:<?= $bg ?>;">
    <div class="hp-container">
        <div class="hp-section-head">
            <div class="hp-section-head__left">
                <h2 class="hp-section-title">✨ <?= $isAr ? 'عطور الماركات العالمية' : 'Global Brand Perfumes' ?></h2>
                <p class="hp-section-sub"><?= $isAr ? 'تشكيلة رائعة من العطور العالمية الفاخرة' : 'An exquisite collection of luxury global fragrances' ?></p>
            </div>
            <a href="<?= esc(url('products.php?is_brand=1')) ?>" class="hp-view-all"><?= $isAr ? 'عرض الكل' : 'View All' ?> <span>›</span></a>
        </div>
        
        <div class="hp-slider-container">
            <button class="hp-slider-arrow hp-slider-arrow--left" onclick="scrollGrid('grid-brand-perfumes', -1)" aria-label="Scroll Left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="hp-grid" id="grid-brand-perfumes">
                <?php foreach ($brandProducts as $i => $p):
                    $showBestseller = true;
                    $offerPrice     = null;
                ?>
                <div class="hp-card-wrap">
                    <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="hp-slider-arrow hp-slider-arrow--right" onclick="scrollGrid('grid-brand-perfumes', 1)" aria-label="Scroll Right">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>


<?php /* ══════════════════════════════════════════
   5. FAQ
══════════════════════════════════════════ */ ?>
<section class="section section--faq" id="faq">
    <div class="container narrow-wide">
        <div class="section-head">
            <h2><?= esc(t('faq_title')) ?></h2>
            <p class="section-sub"><?= esc(t('faq_lead')) ?></p>
        </div>
        <div class="faq-list">
            <?php
            $faqs     = get_all_faqs();
            $currLang = current_lang();
            foreach ($faqs as $f):
                $q = ($currLang === 'ar' ? $f['question_ar'] : $f['question_en']) ?: $f['question_en'];
                $a = ($currLang === 'ar' ? $f['answer_ar']   : $f['answer_en'])   ?: $f['answer_en'];
            ?>
            <details class="faq-item">
                <summary class="faq-item__summary"><?= esc($q) ?></summary>
                <div class="faq-item__body"><?= esc($a) ?></div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function() {
    var details = document.querySelectorAll('.faq-item');
    details.forEach(function(detail) {
        detail.addEventListener('toggle', function() {
            if (this.open) {
                details.forEach(function(other) {
                    if (other !== this && other.open) {
                        other.open = false;
                    }
                }, this);
            }
        });
    });
})();
</script>

<?php /* ══════════════════════════════════════════
   SCOPED STYLES
══════════════════════════════════════════ */ ?>


<?php /* ── Slider Scroll & Arrow Visibility JS ── */ ?>
<script src="<?= url('assets/js/home.js?v=' . filemtime(__DIR__ . '/assets/js/home.js')) ?>" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
