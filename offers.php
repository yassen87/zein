<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$isAr         = current_lang() === 'ar';
$pageTitle    = $isAr ? 'العروض الحصرية' : 'Exclusive Offers';
$pageDescription = get_page_description('offers');
$canonicalUrl = get_current_url_without_lang();
$isOffersPage = true;

// Redirect to home if there are no active offer products at all
if (!has_any_offers()) {
    header('Location: ' . url('index.php'), true, 302);
    exit;
}

// Fixed offer category tabs
$offerCategories = [
    'all'           => $isAr ? 'جميع العروض'     : 'All Offers',
    'women-offers'  => $isAr ? 'عروض حريمي'      : "Women's Offers",
    'men-offers'    => $isAr ? 'عروض رجالي'      : "Men's Offers",
    'unisex-offers' => $isAr ? 'عروض للجنسين'   : 'Unisex Offers',
];

$activeFilter = $_GET['cat'] ?? 'all';
if (!isset($offerCategories[$activeFilter])) $activeFilter = 'all';

// Fetch all active offer products
$allOffers = get_offer_products_localized();

// Apply category filter
$offers = $activeFilter === 'all'
    ? $allOffers
    : array_values(array_filter($allOffers, fn($p) => ($p['category'] ?? '') === $activeFilter));

require __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="page-hero page-hero--compact" style="background: linear-gradient(135deg, #111 0%, #2b2118 100%);">
    <div class="container" style="padding: 4rem 1rem; text-align: center; color: #fff;">
        <span style="background:rgba(197,160,89,0.2); color:#c5a059; padding:0.4rem 1.2rem; border-radius:50px; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:1rem; display:inline-block;">
            <?= $isAr ? 'خصومات وتوفير حصري' : 'Exclusive Deals & Savings' ?>
        </span>
        <h1 style="font-size:clamp(1.5rem,5vw,2.5rem); font-weight:700; margin:0 0 0.5rem;"><?= esc($pageTitle) ?></h1>
        <p style="color:#bbb; max-width:600px; margin:0 auto; font-size:1rem; line-height:1.6;">
            <?= $isAr
                ? 'اكتشف تشكيلتنا الحصرية من العروض الفاخرة بأفضل الأسعار لفترة محدودة'
                : 'Discover our exclusive luxury offers at the best prices for a limited time' ?>
        </p>
    </div>
</section>

<div class="container" style="max-width:1200px; margin:0 auto; padding:0 1rem;">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= esc(url('index.php')) ?>"><?= esc(t('nav_home')) ?></a>
        <span class="breadcrumbs__sep">/</span>
        <span class="breadcrumbs__current"><?= esc($pageTitle) ?></span>
    </nav>
</div>

<!-- Category Filter Tabs -->
<section style="background:#fff; border-bottom:1px solid #f0f0f0; position:sticky; top:0; z-index:100;">
    <div class="container" style="max-width:1200px; margin:0 auto; padding:0 1rem;">
        <div style="display:flex; gap:0.5rem; overflow-x:auto; padding:0.75rem 0; scrollbar-width:none; -ms-overflow-style:none;">
            <?php foreach ($offerCategories as $slug => $label): ?>
            <a href="<?= esc(url('offers.php' . ($slug !== 'all' ? '?cat=' . $slug : ''))) ?>"
               style="white-space:nowrap; padding:0.5rem 1.25rem; border-radius:50px; font-weight:600; font-size:0.875rem; text-decoration:none; transition:all .2s;
                      <?= $activeFilter === $slug
                        ? 'background:#c5a059; color:#fff; border:2px solid #c5a059;'
                        : 'background:transparent; color:#555; border:2px solid #e5e5e5;' ?>">
                <?= esc($label) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Products Grid -->
<section class="section" style="padding:clamp(2rem,5vw,4rem) 0; background:#fafafa;">
    <div class="container" style="max-width:1200px; margin:0 auto; padding:0 1rem;">

        <?php if (empty($offers)): ?>
        <div style="text-align:center; padding:5rem 1rem; background:#fff; border-radius:24px; border:1px solid #f0f0f0;">
            <span style="font-size:3.5rem;">🏷️</span>
            <h3 style="font-size:1.3rem; font-weight:700; margin-top:1rem; color:#333;">
                <?= $isAr ? 'لا توجد عروض في هذا القسم حالياً' : 'No offers in this category yet' ?>
            </h3>
            <p style="color:#888; font-size:0.95rem; margin-top:0.5rem;">
                <?= $isAr ? 'جرب قسماً آخر أو تفقد جميع العروض' : 'Try another category or view all offers' ?>
            </p>
            <a href="<?= esc(url('offers.php')) ?>" class="cart-drawer__btn"
               style="display:inline-block; width:auto; padding:0.8rem 2.5rem; margin-top:1.5rem;">
                <?= $isAr ? 'جميع العروض' : 'All Offers' ?>
            </a>
        </div>
        <?php else: ?>

        <p style="color:#888; font-size:0.875rem; margin-bottom:1.25rem;">
            <?= count($offers) ?> <?= $isAr ? 'عرض متاح' : 'offer(s) available' ?>
        </p>

        <div class="product-grid product-grid--large offers-product-grid"
             style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px,1fr)); gap:1.5rem;">
            <?php foreach ($offers as $offer):
                // Alias for product-card.php compatibility
                $p = $offer;
                ?>
                <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<style>
@media (max-width: 640px) {
    .offers-product-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.75rem !important;
    }
    .page-hero--compact .container { padding: 2.5rem 1rem !important; }
    .page-hero--compact h1 { font-size: 1.4rem !important; }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
