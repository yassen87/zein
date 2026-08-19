<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$pageTitle = t('page_collection');
$pageDescription = get_page_description('products');
$canonicalUrl = get_current_url_without_lang();
$filter = $_GET['cat'] ?? $_GET['category'] ?? 'all';
$brandFilter = isset($_GET['brand']) ? trim((string)$_GET['brand']) : '';
$searchQuery = trim($_GET['q'] ?? '');
$sortBy = $_GET['sort'] ?? 'default';

// Brand title for display
$brandFilterName = '';
$brandFilterNameAr = '';

$extraCss = [
    url('assets/css/pages/products.css?v=' . filemtime(__DIR__ . '/assets/css/pages/products.css'))
];

$valid = ['all', 'offers'];
$pdo = medal_pdo();
$dbCatsForPills = [];
if ($pdo) {
    try {
        $dbCats = $pdo->query("SELECT slug FROM categories")->fetchAll(PDO::FETCH_COLUMN);
        $valid = array_merge($valid, $dbCats);
        $dbCatsForPills = $pdo->query("SELECT * FROM categories WHERE slug NOT IN ('gifts', 'gift', 'hadiya') ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Throwable $e) {}
}
if (!in_array($filter, $valid, true)) $filter = 'all';

// If someone navigates to ?cat=offers but no offers exist, fall back to 'all'
if ($filter === 'offers' && !has_any_offers()) $filter = 'all';

// Handle brand filter: fetch brand products from DB
$brandProducts = null;
if ($brandFilter !== '') {
    $pdo = $pdo ?? medal_pdo();
    if ($brandFilter === 'general') {
        // Products in brands category with no brand_id
        if ($pdo) {
            try {
                $bSt = $pdo->query(
                    "SELECT p.*, pv.id AS default_variant_id, pv.price AS price,
                    (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                    (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                    FROM products p
                    LEFT JOIN product_variants pv ON pv.id = (
                        SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                    )
                    WHERE p.active = 1
                    AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_slug = 'brands')
                    AND (p.brand_id IS NULL OR p.brand_id = 0)
                    ORDER BY p.sort_order ASC, p.id ASC"
                );
                $brandProducts = array_map(fn($r) => localize_product(map_db_product_row($r)), $bSt->fetchAll());
                $brandFilterName = 'General Brands';
                $brandFilterNameAr = 'ماركات عامة';
            } catch (Throwable $e) { $brandProducts = []; }
        }
    } elseif (is_numeric($brandFilter)) {
        $brandId = (int)$brandFilter;
        if ($pdo) {
            try {
                // Get brand name
                $bNameSt = $pdo->prepare('SELECT name_en, name_ar FROM brands WHERE id = ?');
                $bNameSt->execute([$brandId]);
                $bNameRow = $bNameSt->fetch();
                if ($bNameRow) {
                    $brandFilterName = (string)$bNameRow['name_en'];
                    $brandFilterNameAr = (string)$bNameRow['name_ar'];
                }
                // Get brand products
                $bSt = $pdo->prepare(
                    "SELECT p.*, pv.id AS default_variant_id, pv.price AS price,
                    (SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE product_id = p.id) AS average_rating,
                    (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS reviews_count
                    FROM products p
                    LEFT JOIN product_variants pv ON pv.id = (
                        SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = p.id ORDER BY pv2.sort_order ASC, pv2.id ASC LIMIT 1
                    )
                    WHERE p.active = 1 AND p.brand_id = ?
                    ORDER BY p.sort_order ASC, p.id ASC"
                );
                $bSt->execute([$brandId]);
                $brandProducts = array_map(fn($r) => localize_product(map_db_product_row($r)), $bSt->fetchAll());
            } catch (Throwable $e) { $brandProducts = []; }
        }
    }
}

$products = $brandProducts ?? get_products_localized();
$isAr = current_lang() === 'ar';

if ($filter !== 'all') {
    $products = array_values(array_filter($products, static fn ($p) => in_array($filter, $p['categories'] ?? [$p['category']], true)));
}

if ($searchQuery !== '') {
    $searchLang = detect_query_lang($searchQuery);
    $search     = mb_strtolower($searchQuery, 'UTF-8');

    $products = array_values(array_filter($products, static function ($p) use ($search) {
        // Search across ALL language fields so EN query works in AR mode and vice versa
        return mb_strpos(mb_strtolower((string)($p['name']           ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['name_en']        ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['name_ar']        ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description']    ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description_en'] ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['description_ar'] ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['notes']          ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['notes_en']       ?? ''), 'UTF-8'), $search) !== false
            || mb_strpos(mb_strtolower((string)($p['notes_ar']       ?? ''), 'UTF-8'), $search) !== false;
    }));

    // Re-localize results using the query language so names show in what the user typed
    $products = array_map(static fn($p) => localize_product($p, $searchLang), $products);
}

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
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;
$pagedProducts = array_slice($products, $offset, $perPage);

function pagination_url(array $params): string {
    $base = 'products.php';
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
            <?php if ($brandFilter !== ''): ?>
                <a href="<?= esc(url('brands.php')) ?>"><?= $isAr ? 'الماركات' : 'Brands' ?></a>
                <span class="products-hero__breadcrumb-sep">/</span>
                <span class="products-hero__breadcrumb-current"><?= esc($isAr && $brandFilterNameAr ? $brandFilterNameAr : $brandFilterName) ?></span>
            <?php else: ?>
                <span class="products-hero__breadcrumb-current"><?= $filter === 'offers' ? ($isAr ? 'العروض والتخفيضات' : 'Special Offers') : esc($pageTitle) ?></span>
            <?php endif; ?>
        </nav>
        <h1 class="products-hero__title">
            <?php if ($brandFilter !== '' && ($brandFilterName !== '' || $brandFilterNameAr !== '')): ?>
                <?= esc($isAr && $brandFilterNameAr ? $brandFilterNameAr : $brandFilterName) ?>
            <?php elseif ($filter === 'offers'): ?>
                <?= $isAr ? 'العروض والتخفيضات' : 'Special Offers' ?>
            <?php else: ?>
                <?= esc($pageTitle) ?>
            <?php endif; ?>
        </h1>
        <p class="products-hero__sub">
            <?php if ($searchQuery !== ''): ?>
                <?= esc(str_replace(':query', $searchQuery, t('search_results_for'))) ?> · <?= $totalProducts ?> <?= $isAr ? 'منتج' : 'products' ?>
            <?php else: ?>
                <?= $totalProducts ?> <?= $isAr ? 'منتج متاح' : 'products available' ?>
            <?php endif; ?>
        </p>
    </div>
</section>

<div class="products-layout">

    <div class="products-toolbar">
        <form method="GET" action="<?= esc(url('products.php')) ?>" class="products-search">
            <svg class="products-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
            <input type="search" name="q" value="<?= esc($searchQuery) ?>"
                   placeholder="<?= $isAr ? 'ابحث عن عطرك المفضل...' : 'Search for your favorite fragrance...' ?>"
                   class="products-search__input">
            <?php if ($searchQuery !== ''): ?>
                <a href="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-search__clear" aria-label="<?= $isAr ? 'مسح البحث' : 'Clear search' ?>">&times;</a>
            <?php endif; ?>
            <input type="hidden" name="cat" value="<?= $filter === 'all' ? '' : esc($filter) ?>">
            <?php if ($sortBy !== 'default'): ?>
                <input type="hidden" name="sort" value="<?= esc($sortBy) ?>">
            <?php endif; ?>
        </form>

        <div class="products-toolbar__right">
            <span class="products-count">
                <?= $isAr ? 'عرض' : 'Showing' ?> <?= $totalProducts > 0 ? (($page - 1) * $perPage + 1) : 0 ?>-<?= min($totalProducts, $page * $perPage) ?> <?= $isAr ? 'من' : 'of' ?> <?= $totalProducts ?>
            </span>
            <select class="products-sort" onchange="window.location.href=this.value">
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => null])) ?>" <?= $sortBy === 'default' ? 'selected' : '' ?>><?= $isAr ? 'ترتيب افتراضي' : 'Default' ?></option>
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => 'newest'])) ?>" <?= $sortBy === 'newest' ? 'selected' : '' ?>><?= $isAr ? 'الأحدث' : 'Newest' ?></option>
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => 'price_asc'])) ?>" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>><?= $isAr ? 'السعر: من الأقل للأعلى' : 'Price: Low to High' ?></option>
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => 'price_desc'])) ?>" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>><?= $isAr ? 'السعر: من الأعلى للأقل' : 'Price: High to Low' ?></option>
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => 'bestseller'])) ?>" <?= $sortBy === 'bestseller' ? 'selected' : '' ?>><?= $isAr ? 'الأكثر مبيعاً' : 'Best Seller' ?></option>
                <option value="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => 'name_asc'])) ?>" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>><?= $isAr ? 'الاسم: أ-ي' : 'Name: A-Z' ?></option>
            </select>
        </div>
    </div>

    <div class="products-filters">
        <div class="products-filters__scroll">
            <a href="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>"
               class="products-filter-pill <?= $filter === 'all' ? 'products-filter-pill--active' : '' ?>">
                <?= $isAr ? 'الكل' : 'All' ?>
            </a>
            <?php foreach ($dbCatsForPills as $cat):
                $catName = $isAr ? $cat['name_ar'] : $cat['name_en'];
            ?>
                <a href="<?= esc(pagination_url(['cat' => $cat['slug'], 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>"
                   class="products-filter-pill <?= $filter === $cat['slug'] ? 'products-filter-pill--active' : '' ?>">
                    <?= esc($catName) ?>
                </a>
            <?php endforeach; ?>
            <?php if (has_any_offers()): ?>
            <a href="<?= esc(pagination_url(['cat' => 'offers', 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>"
               class="products-filter-pill products-filter-pill--offer <?= $filter === 'offers' ? 'products-filter-pill--active' : '' ?>">
                <?= $isAr ? '🔥 عروض' : '🔥 Offers' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($searchQuery !== '' || $filter !== 'all'): ?>
    <div class="products-active-filters">
        <?php if ($filter !== 'all'): ?>
            <span class="products-active-filter">
                <?= $isAr ? 'القسم:' : 'Category:' ?> <?= esc($filter) ?>
                <a href="<?= esc(pagination_url(['q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-active-filter__remove">&times;</a>
            </span>
        <?php endif; ?>
        <?php if ($searchQuery !== ''): ?>
            <span class="products-active-filter">
                <?= $isAr ? 'بحث:' : 'Search:' ?> "<?= esc($searchQuery) ?>"
                <a href="<?= esc(pagination_url(['cat' => $filter === 'all' ? null : $filter, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-active-filter__remove">&times;</a>
            </span>
        <?php endif; ?>
        <a href="<?= esc(url('products.php')) ?>" class="products-active-filter__clear-all"><?= $isAr ? 'مسح الكل' : 'Clear All' ?></a>
    </div>
    <?php endif; ?>

    <?php if ($filter === 'women'): ?>
    <div class="women-category-banner" style="margin: 1rem 0 1.75rem; background: linear-gradient(135deg, rgba(212, 175, 55, 0.10), rgba(255, 248, 240, 0.98)); border: 1px solid rgba(212, 175, 55, 0.35); border-radius: 14px; padding: 1.15rem 1.4rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: right;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
            <span style="font-size: 1.25rem;">🌸</span>
            <strong style="color: #855d14; font-size: 1rem; font-weight: 700;">تذكرة طيبة لعميلاتنا العزيزات:</strong>
        </div>
        <p style="margin: 0; font-size: 0.92rem; line-height: 1.75; color: #4a3b22; font-weight: 500;">
            يُباح التعطرُ للنساء داخل المنزل، وهو مُستحبّ إذا كان بهدف إدخال السرور على قلب زوجها، ولكنّه يصبح مُحرماً في حالة التعطر والخروج بقصد أن يشمَّه الرجال الأجانب، وتُؤثم المرأة التي تفعل ذلك، لأنّ في عطرها فتنة للرجال.
        </p>
        <div style="margin-top: 0.4rem; font-size: 0.82rem; color: #855d14; font-weight: 600; text-align: left;">
            بنذكر بعض بس 🌸✨
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($pagedProducts)): ?>
        <div class="products-empty">
            <div class="products-empty__icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path><line x1="8" y1="11" x2="14" y2="11"></line></svg>
            </div>
            <h3 class="products-empty__title"><?= $isAr ? 'لا توجد منتجات' : 'No products found' ?></h3>
            <p class="products-empty__text"><?= $isAr ? 'لم نجد أي منتجات تطابق معايير البحث. جرب تغيير الفلتر أو البحث عن شيء آخر.' : 'We couldn\'t find any products matching your criteria. Try changing the filter or searching for something else.' ?></p>
            <a href="<?= esc(url('products.php')) ?>" class="products-empty__btn"><?= $isAr ? 'عرض كل المنتجات' : 'View All Products' ?></a>
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
            <a href="<?= esc(pagination_url(['page' => $page - 1, 'cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__btn">
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
                <a href="<?= esc(pagination_url(['page' => 1, 'cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__page">1</a>
                <?php if ($startPg > 2): ?><span class="products-pagination__dots">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                <a href="<?= esc(pagination_url(['page' => $i, 'cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>"
                   class="products-pagination__page <?= $i === $page ? 'products-pagination__page--active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($endPg < $totalPages): ?>
                <?php if ($endPg < $totalPages - 1): ?><span class="products-pagination__dots">...</span><?php endif; ?>
                <a href="<?= esc(pagination_url(['page' => $totalPages, 'cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__page"><?= $totalPages ?></a>
            <?php endif; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="<?= esc(pagination_url(['page' => $page + 1, 'cat' => $filter === 'all' ? null : $filter, 'q' => $searchQuery ?: null, 'sort' => $sortBy !== 'default' ? $sortBy : null])) ?>" class="products-pagination__btn">
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