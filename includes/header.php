<?php
declare(strict_types=1);
require_once __DIR__ . '/products.php';

$pageTitle = $pageTitle ?? site_name();
$bodyClass = trim('theme-gulf ' . ($bodyClass ?? '') . ' lang-' . preg_replace('/[^a-z]/', '', current_lang()));
$htmlLang = current_lang();
$htmlDir  = is_rtl() ? 'rtl' : 'ltr';
$isAr     = current_lang() === 'ar';

// Fetch categories once
$pdo = medal_pdo();
$headerCats = [];
if ($pdo) {
    try {
        $headerCats = $pdo->query("SELECT * FROM categories WHERE slug NOT IN ('gifts','gift','hadiya') ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Throwable $e) {}
}
$cartCount = cart_count();
$clientLoggedIn = is_client_logged_in();
$clientName = $clientLoggedIn ? ($_SESSION['client_name'] ?? '') : '';
$womenCategoryToast = $_SESSION['women_category_cart_alert'] ?? null;
unset($_SESSION['women_category_cart_alert']);
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>
    try {
        if (localStorage.getItem("medal-theme") !== "dark") {
            document.documentElement.classList.add("daylight");
        }
    } catch (e) {}
    </script>
    
    <!-- Primary Meta Tags -->
    <title><?= esc($pageTitle ?? 'Zain Perfumes') ?> — <?= esc(get_site_name()) ?></title>
    <meta name="description" content="<?= esc($pageDescription ?? get_site_description()) ?>">
    <meta name="robots" content="<?= isset($noindex) && $noindex ? 'noindex, nofollow' : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1' ?>">
    <meta name="author" content="Zain Perfumes">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= esc($canonicalUrl ?? get_current_url_without_lang()) ?>">
    
    <!-- Alternate Language -->
    <link rel="alternate" hreflang="ar" href="<?= esc(get_alternate_lang_url('ar')) ?>">
    <link rel="alternate" hreflang="en" href="<?= esc(get_alternate_lang_url('en')) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= esc(get_alternate_lang_url('ar')) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($canonicalUrl ?? get_current_url_without_lang()) ?>">
    <meta property="og:title" content="<?= esc($pageTitle ?? 'Zain Perfumes') ?> — <?= esc(get_site_name()) ?>">
    <meta property="og:description" content="<?= esc($pageDescription ?? get_site_description()) ?>">
    <meta property="og:image" content="<?= esc($ogImage ?? get_og_image()) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="<?= current_lang() === 'ar' ? 'ar_EG' : 'en_US' ?>">
    <meta property="og:locale:alternate" content="<?= current_lang() === 'ar' ? 'en_US' : 'ar_EG' ?>">
    <meta property="og:site_name" content="<?= esc(get_site_name()) ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= esc($canonicalUrl ?? get_current_url_without_lang()) ?>">
    <meta name="twitter:title" content="<?= esc($pageTitle ?? 'Zain Perfumes') ?> — <?= esc(get_site_name()) ?>">
    <meta name="twitter:description" content="<?= esc($pageDescription ?? get_site_description()) ?>">
    <meta name="twitter:image" content="<?= esc($ogImage ?? get_og_image()) ?>">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#111111">
    <meta name="msapplication-TileColor" content="#d4af37">
    <link rel="icon" type="image/png" href="<?= esc(url('assets/img/logo.png')) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= esc(url('assets/img/logo.png')) ?>">
    <link rel="apple-touch-icon" href="<?= esc(url('assets/img/logo.png')) ?>">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Zain Perfumes",
        "alternateName": "زين للعطور",
        "url": "<?= esc(get_base_url()) ?>",
        "logo": "<?= esc(get_og_image()) ?>",
        "description": "<?= esc(get_site_description()) ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+201234567890",
            "contactType": "customer service",
            "availableLanguage": ["Arabic", "English"]
        },
        "sameAs": []
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Zain Perfumes",
        "alternateName": "زين للعطور",
        "url": "<?= esc(get_base_url()) ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= esc(get_base_url()) ?>/search.php?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <!-- Fonts (async) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=El+Messiri:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=El+Messiri:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap"></noscript>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= esc(url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css'))) ?>">
    <link rel="stylesheet" href="<?= esc(url('assets/css/design-system.css?v=' . filemtime(__DIR__ . '/../assets/css/design-system.css'))) ?>">
    <link rel="stylesheet" href="<?= esc(url('assets/css/theme-daylight.css?v=' . filemtime(__DIR__ . '/../assets/css/theme-daylight.css'))) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/components/header.css?v=' . filemtime(__DIR__ . '/../assets/css/components/header.css')) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/components/footer.css?v=' . filemtime(__DIR__ . '/../assets/css/components/footer.css')) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/components/product-card.css?v=' . filemtime(__DIR__ . '/../assets/css/components/product-card.css')) ?>">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ((array)$extraCss as $cssFile): ?>
        <link rel="stylesheet" href="<?= $cssFile ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script>
    window.BASE_URL = <?= json_encode(rtrim(get_base_url() . base_path(), '/') . '/') ?>;
    window.appUrl = function(path) {
        return window.BASE_URL + String(path || '').replace(/^\/+/, '');
    };
    window.CSRF_TOKEN = <?= json_encode(get_csrf_token()) ?>;
    </script>
</head>
<body class="<?= esc($bodyClass) ?>">
    <a class="skip-link" href="#main"><?= esc(t('skip_content')) ?></a>

    <?php if (!empty($GLOBALS['is_maintenance_admin_bypass'])): ?>
        <div style="background: linear-gradient(135deg, #78350f 0%, #b45309 100%); color: #fef3c7; padding: 0.6rem 1rem; text-align: center; font-size: 0.88rem; font-weight: 700; border-bottom: 2px solid #f59e0b; position: relative; z-index: 9999;">
            🔒 <?= $isAr ? 'وضع الصيانة مُفعل حالياً (المتجر مغلق أمام الزوار، وأنت تتصفح كمسؤول/مطور)' : 'Maintenance Mode is ACTIVE (Store is locked to visitors, you have admin bypass)' ?>
            · <a href="/admin/developer_hub.php" style="color: #ffffff; text-decoration: underline; margin-right: 8px;"><?= $isAr ? 'لوحة تحكم المطورين ⚡' : 'Developer Hub ⚡' ?></a>
        </div>
    <?php endif; ?>

    <div id="screen-toast-container" class="screen-toast-container" aria-live="polite" aria-atomic="true">
        <?php if (!empty($womenCategoryToast)): ?>
            <div class="screen-toast" role="status">
                <p><?= esc($womenCategoryToast) ?></p>
                <button type="button" class="screen-toast__close" aria-label="<?= $isAr ? 'إغلاق' : 'Dismiss' ?>">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Announce bar -->
    <div class="announce-bar" role="note">
        <div class="container announce-bar__inner">
            <span class="announce-bar__spark" aria-hidden="true">✦</span>
            <p class="announce-bar__text"><?= esc(get_setting('announce_shipping')) ?></p>
            <button type="button" class="announce-bar__close" id="announce-bar-close" aria-label="<?= $isAr ? 'إغلاق الإعلان' : 'Dismiss announcement' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    <!-- HEADER -->
    <header class="site-header" id="site-header">
        <div class="container header-inner">

            <!-- Mobile: hamburger (left/right based on dir) -->
            <div class="header-left-mobile">
                <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="<?= $isAr ? 'القائمة' : 'Menu' ?>" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>

            <!-- Logo -->
            <a href="<?= esc(url('')) ?>" class="logo" aria-label="<?= esc(site_name()) ?>" style="display:flex;align-items:center;">
                <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(site_name()) ?>" style="height:38px;width:auto;object-fit:contain;" loading="eager" width="38" height="38">
            </a>

            <div class="header-mobile-actions" aria-label="mobile actions">
                <div class="mobile-lang-switcher">
                    <a href="<?= lang_switch_url('en') ?>" class="<?= current_lang() === 'en' ? 'is-active' : '' ?>">EN</a>
                    <span class="sep"></span>
                    <a href="<?= lang_switch_url('ar') ?>" class="<?= current_lang() === 'ar' ? 'is-active' : '' ?>">AR</a>
                </div>
                <button onclick="openCartDrawer()" class="header-action-btn mobile-cart-btn" aria-label="<?= esc(t('nav_cart')) ?>" type="button">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <span class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount > 0 ? $cartCount : 0 ?></span>
                </button>
            </div>

            <!-- Desktop Nav -->
            <nav class="desktop-nav" aria-label="main navigation">
                <a href="<?= esc(url('')) ?>" class="<?= $pageTitle === t('page_home') ? 'is-active' : '' ?>">
                    <?= esc(t('nav_home')) ?>
                </a>

                <!-- Categories Dropdown -->
                <?php if (!empty($headerCats)): ?>
                <div class="nav-dropdown" id="nav-cat-dropdown">
                    <button class="nav-dropdown__btn <?= isset($_GET['cat']) ? 'is-active' : '' ?>" aria-haspopup="true">
                        <?= $isAr ? 'الأقسام' : 'Categories' ?>
                        <svg class="nav-dropdown__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="nav-dropdown__menu" role="menu">
                        <a href="<?= esc(url('products.php')) ?>" role="menuitem">
                            <?= $isAr ? '🛍 جميع المنتجات' : '🛍 All Products' ?>
                        </a>
                        <div class="drop-divider"></div>
                        <?php foreach ($headerCats as $cat):
                            $catName = $isAr ? $cat['name_ar'] : $cat['name_en'];
                        ?>
                        <a href="<?= esc(url('products.php?cat=' . $cat['slug'])) ?>" role="menuitem"
                           <?= (isset($_GET['cat']) && $_GET['cat'] === $cat['slug']) ? 'style="color:var(--gold);"' : '' ?>>
                            <?= esc($catName) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="<?= esc(url('brands.php')) ?>" class="<?= $pageTitle === t('nav_brands') ? 'is-active' : '' ?>">
                    <?= esc(t('nav_brands')) ?>
                </a>
                <?php if (has_any_offers()): ?>
                <a href="<?= esc(url('offers.php')) ?>" class="<?= (isset($isOffersPage) && $isOffersPage) ? 'is-active' : '' ?>">
                    <?= esc(t('filter_offers')) ?>
                </a>
                <?php endif; ?>
                <a href="<?= esc(url('track_order.php')) ?>" class="<?= (isset($isTrackPage) && $isTrackPage) ? 'is-active' : '' ?>">
                    <?= $isAr ? 'تتبع طلبك' : 'Track Order' ?>
                </a>
                <a href="<?= esc(url('about.php')) ?>" class="<?= $pageTitle === t('page_story') ? 'is-active' : '' ?>">
                    <?= $isAr ? 'من نحن' : 'About' ?>
                </a>
                <a href="<?= esc(url('contact.php')) ?>" class="<?= $pageTitle === t('page_contact') ? 'is-active' : '' ?>">
                    <?= $isAr ? 'تواصل' : 'Contact' ?>
                </a>
            </nav>

            <!-- Desktop Right -->
            <div class="header-right">
                <div class="header-search-desktop search-box-container">
                    <form action="<?= esc(url('products.php')) ?>" method="GET" class="header-search">
                        <input type="search" name="q" class="header-search__input" placeholder="<?= esc(t('search_placeholder')) ?>" autocomplete="off">
                        <button type="button" class="header-search__clear" aria-label="<?= $isAr ? 'مسح البحث' : 'Clear search' ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        <button type="submit" class="header-search__btn" aria-label="<?= $isAr ? 'بحث' : 'Search' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </button>
                    </form>
                    <div class="header-search__dropdown" id="search-dropdown" style="display:none;">
                        <div class="search-results" id="search-results"></div>
                        <a href="" class="search-view-all" id="search-view-all"><?= t('view_all_results') ?> →</a>
                    </div>
                </div>
                <div class="lang-switcher">
                    <a href="<?= lang_switch_url('en') ?>" class="<?= current_lang() === 'en' ? 'is-active' : '' ?>">EN</a>
                    <span class="sep"></span>
                    <a href="<?= lang_switch_url('ar') ?>" class="<?= current_lang() === 'ar' ? 'is-active' : '' ?>">AR</a>
                </div>

                <?php if ($clientLoggedIn): ?>
                <a href="<?= esc(url('client/dashboard.php')) ?>" class="header-account-btn desktop-only" title="<?= esc($clientName) ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="header-account-name"><?= esc($clientName ?: ($isAr ? 'حسابي' : 'Account')) ?></span>
                </a>
                <?php else: ?>
                <a href="<?= esc(url('client/login.php')) ?>" class="header-account-btn desktop-only">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="header-account-name"><?= $isAr ? 'تسجيل الدخول' : 'Sign In' ?></span>
                </a>
                <?php endif; ?>

                <button onclick="openCartDrawer()" class="header-action-btn desktop-only" aria-label="<?= esc(t('nav_cart')) ?>" style="background:none;border:none;cursor:pointer;padding:0;position:relative;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php else: ?>
                        <span class="cart-badge" style="display:none;">0</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Overlay -->
    <div class="mobile-drawer-overlay" id="mobile-overlay" aria-hidden="true"></div>

    <!-- Mobile Drawer -->
    <nav class="mobile-drawer" id="mobile-drawer" aria-label="mobile navigation" aria-hidden="true">
        <div class="mobile-drawer__head">
            <a href="<?= esc(url('')) ?>" class="mobile-drawer__logo"><?= esc(site_name()) ?></a>
            <button class="mobile-drawer__close" id="mobile-drawer-close" aria-label="<?= $isAr ? 'إغلاق' : 'Close' ?>">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mobile-drawer__body">
            <form action="<?= esc(url('products.php')) ?>" method="GET" class="header-search header-search--mobile">
                <input type="search" name="q" class="header-search__input" placeholder="<?= esc(t('search_placeholder')) ?>" autocomplete="off">
                <button type="submit" class="header-search__btn" aria-label="<?= $isAr ? 'بحث' : 'Search' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </form>
            <!-- Main -->
            <p class="mobile-drawer__section-title"><?= $isAr ? 'الصفحات' : 'Pages' ?></p>
            <a href="<?= esc(url('')) ?>" class="mobile-drawer__link <?= $pageTitle === t('page_home') ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                <?= $isAr ? 'الرئيسية' : 'Home' ?>
            </a>
            <a href="<?= esc(url('brands.php')) ?>" class="mobile-drawer__link <?= $pageTitle === t('nav_brands') ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32"/></svg>
                <?= esc(t('nav_brands')) ?>
            </a>
            <?php if (has_any_offers()): ?>
            <a href="<?= esc(url('offers.php')) ?>" class="mobile-drawer__link <?= (isset($isOffersPage) && $isOffersPage) ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <?= esc(t('filter_offers')) ?>
            </a>
            <?php endif; ?>
            <a href="<?= esc(url('track_order.php')) ?>" class="mobile-drawer__link <?= (isset($isTrackPage) && $isTrackPage) ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= $isAr ? 'تتبع طلبك' : 'Track Order' ?>
            </a>

            <!-- Categories -->
            <?php if (!empty($headerCats)): ?>
            <div class="mobile-drawer__divider"></div>
            <p class="mobile-drawer__section-title"><?= $isAr ? 'الأقسام' : 'Categories' ?></p>
            <a href="<?= esc(url('products.php')) ?>" class="mobile-drawer__link">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <?= $isAr ? 'جميع المنتجات' : 'All Products' ?>
            </a>
            <?php foreach ($headerCats as $cat):
                $catName = $isAr ? $cat['name_ar'] : $cat['name_en'];
                $active = (isset($_GET['cat']) && $_GET['cat'] === $cat['slug']) ? 'is-active' : '';
            ?>
            <a href="<?= esc(url('products.php?cat=' . $cat['slug'])) ?>" class="mobile-drawer__link <?= $active ?>">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                <?= esc($catName) ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Info -->
            <div class="mobile-drawer__divider"></div>
            <p class="mobile-drawer__section-title"><?= $isAr ? 'معلومات' : 'Info' ?></p>
            <a href="<?= esc(url('about.php')) ?>" class="mobile-drawer__link <?= $pageTitle === t('page_story') ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= $isAr ? 'من نحن' : 'About Us' ?>
            </a>
            <a href="<?= esc(url('contact.php')) ?>" class="mobile-drawer__link <?= $pageTitle === t('page_contact') ? 'is-active' : '' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.13 12 19.79 19.79 0 0 1 1.06 3.36 2 2 0 0 1 3.05 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= $isAr ? 'تواصل معنا' : 'Contact Us' ?>
            </a>
        </div>

        <!-- Account section at bottom of drawer -->
        <div class="mobile-drawer__divider"></div>
        <?php if ($clientLoggedIn): ?>
        <a href="<?= esc(url('client/dashboard.php')) ?>" class="mobile-drawer__link mobile-drawer__account-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= $isAr ? 'حسابي' : 'My Account' ?>
            <?php if ($clientName): ?><span style="opacity:0.6;font-size:0.75rem;margin-<?= $isAr ? 'right' : 'left' ?>:auto"><?= esc($clientName) ?></span><?php endif; ?>
        </a>
        <a href="<?= esc(url('client/logout.php')) ?>" class="mobile-drawer__link" style="color:#c0392b">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <?= $isAr ? 'تسجيل الخروج' : 'Sign Out' ?>
        </a>
        <?php else: ?>
        <a href="<?= esc(url('client/login.php')) ?>" class="mobile-drawer__link mobile-drawer__account-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= $isAr ? 'تسجيل الدخول' : 'Sign In' ?>
        </a>
        <a href="<?= esc(url('client/register.php')) ?>" class="mobile-drawer__link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <?= $isAr ? 'إنشاء حساب' : 'Create Account' ?>
        </a>
        <?php endif; ?>

        <!-- Language switch at bottom of drawer -->
        <div class="mobile-drawer__lang">
            <a href="<?= lang_switch_url('ar') ?>" class="<?= current_lang() === 'ar' ? 'is-active' : '' ?>">عربي</a>
            <a href="<?= lang_switch_url('en') ?>" class="<?= current_lang() === 'en' ? 'is-active' : '' ?>">English</a>
        </div>

    </nav>

    <script>
    (function(){
        var storageKey = 'zain-announce-dismissed';
        var bar = document.querySelector('.announce-bar');
        var closeBtn = document.getElementById('announce-bar-close');

        if (!bar) return;

        try {
            if (localStorage.getItem(storageKey) === '1') {
                bar.classList.add('announce-bar--hidden');
                document.body.classList.add('announce-bar-is-hidden');
            }
        } catch (e) {}

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                bar.classList.add('announce-bar--hidden');
                document.body.classList.add('announce-bar-is-hidden');
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            });
        }
    })();

    (function(){
        var btn     = document.getElementById('mobile-menu-btn');
        var drawer  = document.getElementById('mobile-drawer');
        var overlay = document.getElementById('mobile-overlay');
        var closeBtn = document.getElementById('mobile-drawer-close');
        var scrollY = 0;

        if (!btn || !drawer || !overlay || !closeBtn) return;

        function openDrawer() {
            scrollY = window.scrollY || window.pageYOffset;
            btn.classList.add('is-open');
            drawer.classList.add('is-open');
            overlay.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            drawer.setAttribute('aria-hidden', 'false');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + scrollY + 'px';
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            btn.classList.remove('is-open');
            drawer.classList.remove('is-open');
            overlay.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            drawer.setAttribute('aria-hidden', 'true');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.left = '';
            document.body.style.right = '';
            document.body.style.overflow = '';
            window.scrollTo(0, scrollY);
        }

        btn.addEventListener('click', function() {
            drawer.classList.contains('is-open') ? closeDrawer() : openDrawer();
        });
        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer();
        });
    })();
    </script>

    <main id="main">
