<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$query = $_GET['q'] ?? '';
$pageTitle = $query ? t('search_results_for', ['query' => $query]) : t('search_placeholder');
$pageDescription = current_lang() === 'ar' ? 'ابحث في متجر زين للعطور عن العطور الفاخرة والعطور العربية والفرنسية.' : 'Search Zain Perfumes for luxury fragrances, Arabic and French perfumes.';
$canonicalUrl = get_current_url_without_lang();

// get_products_localized() now keeps name_ar/name_en etc. for cross-language search
$allProducts      = get_products_localized();
$filteredProducts = [];

if ($query) {
    $queryLower = mb_strtolower($query, 'UTF-8');
    // Detect what language the user typed in
    $searchLang = detect_query_lang($query);

    foreach ($allProducts as $p) {
        // Build a combined search corpus from BOTH languages
        $corpus = implode(' ', [
            $p['name']           ?? '',
            $p['name_en']        ?? '',
            $p['name_ar']        ?? '',
            $p['description']    ?? '',
            $p['description_en'] ?? '',
            $p['description_ar'] ?? '',
            $p['notes']          ?? '',
            $p['notes_en']       ?? '',
            $p['notes_ar']       ?? '',
            $p['category']       ?? '',
        ]);
        if (mb_strpos(mb_strtolower($corpus, 'UTF-8'), $queryLower) !== false) {
            // Re-localize using the query language so the name shows in the searched language
            $filteredProducts[] = localize_product($p, $searchLang);
        }
    }
} else {
    $filteredProducts = $allProducts;
}

// Pagination
$perPage    = 10;
$totalItems = count($filteredProducts);
$totalPages = (int) ceil($totalItems / $perPage);
$currentPage = max(1, min((int)($_GET['page'] ?? 1), max(1, $totalPages)));
$offset      = ($currentPage - 1) * $perPage;
$pageProducts = array_slice($filteredProducts, $offset, $perPage);

// Build pagination URL helper
function search_page_url(string $q, int $page): string {
    $params = ['q' => $q, 'page' => $page];
    return url('search.php') . '?' . http_build_query($params);
}

require __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 100px;">
    <div class="search-page-header" style="margin-bottom: 1.5rem; text-align: center;">
        <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;">
            <?= $query ? esc(t('search_results_for', ['query' => $query])) : esc(t('search_placeholder')) ?>
        </h1>

        <form action="<?= esc(url('search.php')) ?>" method="GET" style="max-width: 500px; margin: 0 auto; position: relative;">
            <input type="search" name="q" value="<?= esc($query) ?>"
                   placeholder="<?= esc(t('search_placeholder')) ?>"
                   style="width: 100%; padding: 0.9rem 1.5rem; border-radius: 30px; border: 2px solid #eee; font-size: 1rem; outline: none; transition: border-color 0.3s;"
                   onfocus="this.style.borderColor='var(--color-primary, #000)'"
                   onblur="this.style.borderColor='#eee'">
            <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
            </button>
        </form>

        <?php if ($totalItems > 0): ?>
        <p style="margin-top: 0.75rem; color: #888; font-size: 0.88rem;">
            <?php
            $from = $offset + 1;
            $to   = min($offset + $perPage, $totalItems);
            echo current_lang() === 'ar'
                ? "عرض {$from}–{$to} من {$totalItems} نتيجة"
                : "Showing {$from}–{$to} of {$totalItems} results";
            ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if (empty($filteredProducts)): ?>
        <div style="text-align: center; padding: 4rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.2;">🔍</div>
            <p style="color: #666; font-size: 1.1rem;"><?= esc(t('search_no_results')) ?></p>
            <a href="<?= esc(url('products.php')) ?>" class="btn btn-primary" style="margin-top: 1.5rem; display: inline-block;"><?= esc(t('view_collection')) ?></a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($pageProducts as $p): ?>
                <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav aria-label="Search pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2.5rem; flex-wrap: wrap;">
            <?php if ($currentPage > 1): ?>
                <a href="<?= esc(search_page_url($query, $currentPage - 1)) ?>"
                   style="display:flex; align-items:center; gap:0.3rem; padding: 0.55rem 1.1rem; border: 1.5px solid #ddd; border-radius: 50px; font-size: 0.9rem; color: #333; text-decoration: none; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='#c5a059'; this.style.color='#c5a059';"
                   onmouseout="this.style.borderColor='#ddd'; this.style.color='#333';">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    <?= current_lang() === 'ar' ? 'السابق' : 'Prev' ?>
                </a>
            <?php endif; ?>

            <?php
            // Show page numbers with ellipsis
            $range = 2;
            for ($p = 1; $p <= $totalPages; $p++):
                if ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= $range):
                    $isActive = $p === $currentPage;
            ?>
                <a href="<?= esc(search_page_url($query, $p)) ?>"
                   style="display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:50%; border: 1.5px solid <?= $isActive ? '#c5a059' : '#ddd' ?>; font-size: 0.9rem; font-weight: <?= $isActive ? '700' : '400' ?>; color: <?= $isActive ? '#c5a059' : '#555' ?>; text-decoration: none; background: <?= $isActive ? '#fff9f0' : '#fff' ?>; transition: all 0.2s;"
                   <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <?= $p ?>
                </a>
            <?php
                elseif (abs($p - $currentPage) === $range + 1):
            ?>
                <span style="color:#bbb; padding: 0 0.2rem;">…</span>
            <?php
                endif;
            endfor;
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= esc(search_page_url($query, $currentPage + 1)) ?>"
                   style="display:flex; align-items:center; gap:0.3rem; padding: 0.55rem 1.1rem; border: 1.5px solid #ddd; border-radius: 50px; font-size: 0.9rem; color: #333; text-decoration: none; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='#c5a059'; this.style.color='#c5a059';"
                   onmouseout="this.style.borderColor='#ddd'; this.style.color='#333';">
                    <?= current_lang() === 'ar' ? 'التالي' : 'Next' ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
