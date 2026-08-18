<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$isAr = current_lang() === 'ar';
$pageTitle = $isAr ? 'عطور الماركات العالمية' : 'Global Brand Perfumes';
$pageDescription = $isAr ? 'تصفح تشكيلتنا المميزة من عطور الماركات العالمية المتوفرة في زين للعطور.' : 'Browse our premium collection of global brand perfumes available at Zain Perfumes.';
$canonicalUrl = get_current_url_without_lang();
$searchQuery = trim($_GET['q'] ?? '');
$sortBy = $_GET['sort'] ?? 'default';

$extraCss = [
    url('assets/css/pages/products.css?v=' . filemtime(__DIR__ . '/assets/css/pages/products.css'))
];

$pdo = medal_pdo();
$products = [];

if ($pdo) {
    try {
        // Fetch all active brand products
        $sql = "SELECT p.*, pv.id AS default_variant_id, pv.price AS price,
                (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = (
                    SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                )
                WHERE p.active = 1
                AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug = 'brands')
                ORDER BY p.sort_order ASC, p.id ASC";
        $rows = $pdo->query($sql)->fetchAll();
        foreach ($rows as $r) {
            $products[] = localize_product(map_db_product_row($r));
        }
    } catch (Throwable $e) {
        $products = [];
    }
}

// Handle search query
if ($searchQuery !== '') {
    $searchLang = detect_query_lang($searchQuery);
    $search     = mb_strtolower($searchQuery, 'UTF-8');

    $products = array_values(array_filter($products, static function ($p) use ($search) {
        return mb_strpos(mb_strtolower((string)($p['name']           ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['name_en']        ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['name_ar']        ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description']    ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description_en'] ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description_ar'] ?? ''), 'UTF-8'), $search) !== false;
    }));

    $products = array_map(static fn($p) => localize_product($p, $searchLang), $products);
}

// Handle sorting
switch ($sortBy) {
    case 'price_asc':
        usort($products, fn($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));
        break;
    case 'price_desc':
        usort($products, fn($a, $b) => ($b['price'] ?? 0) <=> ($a['price'] ?? 0));
        break;
    case 'newest':
        $products = array_reverse($products);
        break;
    case 'bestseller':
        usort($products, fn($a, $b) => ($b['bestseller'] ?? false) <=> ($a['bestseller'] ?? false));
        break;
    case 'name_asc':
        usort($products, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        break;
}

$totalProducts = count($products);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$totalPages = (int)ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$pagedProducts = array_slice($products, $offset, $perPage);

function pagination_url(array $params): string {
    $base = 'brands.php';
    $query = http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
    return url($base . ($query ? '?' . $query : ''));
}

require __DIR__ . '/includes/header.php';
?>

<section class="products-hero">
    <div class="products-hero__inner">
        <nav class="products-hero__breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= esc(url('index.php')) ?>"><?= esc(t('nav_home')) ?></a>
            <span class="products-hero__breadcrumb-sep">/</span>
            <span class="products-hero__breadcrumb-current"><?= esc($pageTitle) ?></span>
        </nav>
        <h1 class="products-hero__title"><?= esc($pageTitle) ?></h1>
        <p class="products-hero__sub">
            <?= $totalProducts ?> <?= $isAr ? 'عطر ماركة متاح' : 'brand perfumes available' ?>
        </p>
    </div>
</section>

<div class="products-layout">
    <div class="products-toolbar">
        <form method="GET" action="<?= esc(url('brands.php')) ?>" class="products-search">
            <svg class="products-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
            <input type="search" name="q" value="<?= esc($searchQuery) ?>"
                   placeholder="<?= $isAr ? 'ابحث عن عطر ماركة...' : 'Search for a brand perfume...' ?>"
                   class="products-search__input">
            <?php if ($searchQuery !== ''): ?>
                <a href="<?= esc(pagination_url(['sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-search__clear" aria-label="<?= $isAr ? 'مسح البحث' : 'Clear search' ?>">&times;</a>
            <?php endif; ?>
            <?php if ($sortBy !== 'default'): ?>
                <input type="hidden" name="sort" value="<?= esc($sortBy) ?>">
            <?php endif; ?>
        </form>

        <div class="products-toolbar__right">
            <span class="products-count">
                <?= $isAr ? 'عرض' : 'Showing' ?> <?= $totalProducts > 0 ? (($page - 1) * $perPage + 1) : 0 ?>-<?= min($totalProducts, $page * $perPage) ?> <?= $isAr ? 'من' : 'of' ?> <?= $totalProducts ?>
            </span>
            <select class="products-sort" onchange="window.location.href=this.value">
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => null])) ?>" <?= $sortBy === 'default' ? 'selected' : '' ?>><?= $isAr ? 'ترتيب افتراضي' : 'Default' ?></option>
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => 'newest'])) ?>" <?= $sortBy === 'newest' ? 'selected' : '' ?>><?= $isAr ? 'الأحدث' : 'Newest' ?></option>
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => 'price_asc'])) ?>" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>><?= $isAr ? 'السعر: من الأقل للأعلى' : 'Price: Low to High' ?></option>
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => 'price_desc'])) ?>" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>><?= $isAr ? 'السعر: من الأعلى للأقل' : 'Price: High to Low' ?></option>
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => 'bestseller'])) ?>" <?= $sortBy === 'bestseller' ? 'selected' : '' ?>><?= $isAr ? 'الأكثر مبيعاً' : 'Best Seller' ?></option>
                <option value="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => 'name_asc'])) ?>" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>><?= $isAr ? 'الاسم: أ-ي' : 'Name: A-Z' ?></option>
            </select>
        </div>
    </div>

    <?php if ($searchQuery !== ''): ?>
    <div class="products-active-filters">
        <span class="products-active-filter">
            <?= $isAr ? 'بحث:' : 'Search:' ?> "<?= esc($searchQuery) ?>"
            <a href="<?= esc(pagination_url(['sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-active-filter__remove">&times;</a>
        </span>
        <a href="<?= esc(url('brands.php')) ?>" class="products-active-filter__clear-all"><?= $isAr ? 'مسح الكل' : 'Clear All' ?></a>
    </div>
    <?php endif; ?>

    <?php if (empty($pagedProducts)): ?>
        <div class="products-empty">
            <div class="products-empty__icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path><line x1="8" y1="11" x2="14" y2="11"></line></svg>
            </div>
            <h3 class="products-empty__title"><?= $isAr ? 'لا توجد منتجات' : 'No products found' ?></h3>
            <p class="products-empty__text"><?= $isAr ? 'لم نجد أي عطور ماركات تطابق معايير البحث. جرب البحث عن شيء آخر.' : 'We couldn\'t find any brand perfumes matching your criteria. Try searching for something else.' ?></p>
            <a href="<?= esc(url('brands.php')) ?>" class="products-empty__btn"><?= $isAr ? 'عرض كل العطور الماركة' : 'View All Brand Perfumes' ?></a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($pagedProducts as $p): ?>
                <?php $showBestseller = false; require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <nav class="products-pagination" aria-label="<?= t('pagination') ?>">
        <?php if ($page > 1): ?>
            <a href="<?= esc(pagination_url(['page' => $page - 1, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <?= $isAr ? 'السابق' : 'Previous' ?>
            </a>
        <?php else: ?>
            <span class="products-pagination__btn products-pagination__btn--disabled">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <?= $isAr ? 'السابق' : 'Previous' ?>
            </span>
        <?php endif; ?>

        <div class="products-pagination__pages">
            <?php
            $startPg = max(1, $page - 2);
            $endPg = min($totalPages, $page + 2);
            if ($startPg > 1): ?>
                <a href="<?= esc(pagination_url(['page' => 1, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__page">1</a>
                <?php if ($startPg > 2): ?><span class="products-pagination__dots">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                <a href="<?= esc(pagination_url(['page' => $i, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>"
                   class="products-pagination__page <?= $i === $page ? 'products-pagination__page--active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($endPg < $totalPages): ?>
                <?php if ($endPg < $totalPages - 1): ?><span class="products-pagination__dots">...</span><?php endif; ?>
                <a href="<?= esc(pagination_url(['page' => $totalPages, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__page"><?= $totalPages ?></a>
            <?php endif; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="<?= esc(pagination_url(['page' => $page + 1, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__btn">
                <?= $isAr ? 'التالي' : 'Next' ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        <?php else: ?>
            <span class="products-pagination__btn products-pagination__btn--disabled">
                <?= $isAr ? 'التالي' : 'Next' ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
