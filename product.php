<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/products.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id === 0 && !empty($_GET['slug'])) {
    $slugVal = trim((string)$_GET['slug']);
    $pdoInit = medal_pdo();
    if ($pdoInit) {
        $stInit = $pdoInit->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
        $stInit->execute([$slugVal]);
        $id = (int)($stInit->fetchColumn() ?: 0);
    }
}
$product = $id > 0 ? get_product_by_id_localized($id) : null;

$pdo = null;
if ($product !== null) {
    try {
        $pdo = medal_pdo();
        if ($pdo) {
            $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
        }
    } catch (Throwable $e) {
        error_log('Error in product.php view_count update: ' . $e->getMessage());
    }
}

$reviewSuccess = '';
if (isset($_SESSION['review_success'])) {
    $reviewSuccess = current_lang() === 'ar' ? 'تم إضافة تقييمك بنجاح! شكرًا لمشاركتنا رأيك.' : 'Your review was submitted successfully! Thank you for sharing your feedback.';
    unset($_SESSION['review_success']);
}

$reviews = [];
$averageRating = 5.0;

if ($product !== null && $pdo) {
    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        if (!validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
            $reviewSuccess = current_lang() === 'ar' ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : 'Session expired. Please refresh the page and try again.';
            $pageTitle = $product['name'];
            require __DIR__ . '/includes/header.php';
            echo '<div style="text-align:center;padding:3rem;color:#b91c1c;"><p>' . esc($reviewSuccess) . '</p></div>';
            require __DIR__ . '/includes/footer.php';
            exit;
        }
        $customerName = trim((string)($_POST['customer_name'] ?? ''));
        $rating = (int)($_POST['rating'] ?? 5);
        $reviewText = trim((string)($_POST['review_text'] ?? ''));
        
        if ($customerName !== '') {
            try {
                $st = $pdo->prepare("INSERT INTO product_reviews (product_id, customer_name, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())");
                $st->execute([$id, $customerName, $rating, $reviewText !== '' ? $reviewText : null]);
                
                $_SESSION['review_success'] = true;
                $redirectUrl = str_replace(["\r", "\n"], '', $_SERVER['REQUEST_URI']);
                $redirectUrl = filter_var($redirectUrl, FILTER_SANITIZE_URL);
                header("Location: " . $redirectUrl);
                exit;
} catch (Throwable $e) {
                error_log('Error in product.php review insert: ' . $e->getMessage());
            }
        }
    }

    // Fetch product reviews
    try {
        $st = $pdo->prepare("SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC");
        $st->execute([$id]);
        $reviews = $st->fetchAll();
        
        if (count($reviews) > 0) {
            $totalStars = array_sum(array_column($reviews, 'rating'));
            $averageRating = round($totalStars / count($reviews), 1);
        }
    } catch (Throwable $e) {
        error_log('Error in product.php reviews fetch: ' . $e->getMessage());
    }
}

if ($product === null) {
    http_response_code(404);
    $pageTitle = t('product_not_found_title');
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container narrow">
            <h1><?= esc(t('product_not_found_title')) ?></h1>
            <p><?= esc(t('product_not_found_text')) ?> <a href="<?= esc(url('products.php')) ?>"><?= esc(t('product_not_found_link')) ?></a>.</p>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$pageDescription = $product['description'] ?? $product['notes'] ?? get_site_description();
$canonicalUrl = get_current_url_without_lang();

$variants = get_product_variants($id);
$selectedVariantId = null;
if ($variants !== []) {
    $req = isset($_GET['v']) ? (int) $_GET['v'] : 0;
    foreach ($variants as $v) {
        if ($req > 0 && $v['id'] === $req) {
            $selectedVariantId = $v['id'];
            break;
        }
    }
    if ($selectedVariantId === null) {
        $selectedVariantId = $variants[0]['id'];
    }
    $rv = resolve_product_variant($id, $selectedVariantId);
    if ($rv !== null) {
        $product['price'] = $rv['price'];
    }
}

// Detect if this is an offer or brand product (hide variant selector)
$isOfferProduct = !empty($product['is_offer']) || !empty($product['is_brand_product']) || in_array('offers', $product['categories'] ?? [$product['category']], true) || in_array('brands', $product['categories'] ?? [$product['category']], true);

// For offers/brands: get compare_at_price from the default variant
$compareAtPrice = null;
if ($isOfferProduct && $pdo && $selectedVariantId) {
    try {
        $cvSt = $pdo->prepare('SELECT compare_at_price FROM product_variants WHERE id = ?');
        $cvSt->execute([$selectedVariantId]);
        $cvRow = $cvSt->fetch();
        if ($cvRow && $cvRow['compare_at_price'] !== null && (float)$cvRow['compare_at_price'] > $product['price']) {
            $compareAtPrice = (float)$cvRow['compare_at_price'];
        }
    } catch (Throwable $e) {
        error_log('Error in product.php compare_at_price: ' . $e->getMessage());
    }
}

// Calculate discount info if available (simulated for UI)
$discountPercent = 25;
$originalPrice = $product['price'] / (1 - ($discountPercent / 100));
$savings = $originalPrice - $product['price'];

$bodyClass = 'hide-main-header hide-bottom-nav';

$imgUrl = '';
if ($product['image'] && (strpos($product['image'], 'http') === 0)) {
    $imgUrl = $product['image'];
} elseif ($product['image']) {
    $imgUrl = url('assets/uploads/' . $product['image']);
}

// Fetch product gallery images
$galleryImages = [];
if ($pdo && $id > 0) {
    try {
        $giSt = $pdo->prepare('SELECT image_key FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
        $giSt->execute([$id]);
        $galleryImages = $giSt->fetchAll(\PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        error_log('Error in product.php gallery: ' . $e->getMessage());
    }
}
// If no gallery images, use the main image as fallback
if ($galleryImages === [] && $imgUrl !== '') {
    $galleryImages = [$product['image']];
}

// Load related products
$related = [];
if ($product) {
    $related = get_products_by_category($product['category']);
    $related = array_filter($related, fn($p) => $p['id'] !== $product['id']);
    $related = array_slice($related, 0, 4);
}

$ogImage = !empty($imgUrl) ? $imgUrl : get_og_image();

$extraCss = [
    url('assets/css/pages/product.css?v=' . filemtime(__DIR__ . '/assets/css/pages/product.css'))
];

require __DIR__ . '/includes/header.php';
?>

<!-- Product JSON-LD Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "<?= esc($product['name']) ?>",
    "description": "<?= esc($pageDescription) ?>",
    "image": "<?= esc($imgUrl) ?>",
    "sku": "ZAIN-<?= esc((string)$product['id']) ?>",
    <?php if ($isOfferProduct && $compareAtPrice !== null): ?>
    "offers": {
        "@type": "Offer",
        "url": "<?= esc($canonicalUrl) ?>",
        "priceCurrency": "EGP",
        "price": "<?= esc((string)$product['price']) ?>",
        "priceValidUntil": "<?= date('Y-m-d', strtotime('+1 year')) ?>",
        "availability": "https://schema.org/InStock",
        "seller": {
            "@type": "Organization",
            "name": "Zain Perfumes"
        }
    }
    <?php else: ?>
    "offers": {
        "@type": "Offer",
        "url": "<?= esc($canonicalUrl) ?>",
        "priceCurrency": "EGP",
        "price": "<?= esc((string)$product['price']) ?>",
        "availability": "https://schema.org/InStock",
        "seller": {
            "@type": "Organization",
            "name": "Zain Perfumes"
        }
    }
    <?php endif; ?>
    <?php if (!empty($reviews)): ?>
    ,"aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?= esc((string)$averageRating) ?>",
        "reviewCount": "<?= esc((string)count($reviews)) ?>"
    }
    <?php endif; ?>
}
</script>

<!-- Product Nav Bar -->
<div class="product-nav">
    <button type="button" onclick="openCartDrawer()" class="product-nav__action product-nav__cart" aria-label="<?= esc(t('nav_cart')) ?>">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <?php $cartCount = cart_count(); ?>
        <span class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount > 0 ? $cartCount : 0 ?></span>
    </button>
    <a href="<?= esc(url('')) ?>" class="product-nav__logo" aria-label="<?= esc(site_name()) ?>">
        <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(site_name()) ?>">
    </a>
    <a href="javascript:history.back()" class="product-nav__action product-nav__back">
        <span><?= current_lang() === 'ar' ? 'رجوع' : 'Back' ?></span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
    </a>
</div>

<!-- ═══════════════ PDP MAIN ═══════════════ -->
<div class="pdp">

    <!-- Breadcrumbs -->
    <nav class="pdp-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= esc(url('index.php')) ?>"><?= esc(t('nav_home')) ?></a>
        <span class="pdp-breadcrumbs__sep">/</span>
        <a href="<?= esc(url('products.php')) ?>"><?= esc(t('page_collection')) ?></a>
        <span class="pdp-breadcrumbs__sep">/</span>
        <span class="pdp-breadcrumbs__current"><?= esc($product['name']) ?></span>
    </nav>

    <!-- Two-Column Layout -->
    <div class="pdp-layout">

        <!-- === GALLERY COLUMN === -->
        <div class="pdp-gallery">
            <div class="pdp-main-image-wrap" id="pdp-main-image-wrap">
                <img src="<?= esc($imgUrl) ?>" alt="<?= esc($product['name']) ?>" class="pdp-main-image" id="pdp-main-image">
                <button class="pdp-share" onclick="if(navigator.share) navigator.share({title: '<?= esc($product['name']) ?>', url: window.location.href})" aria-label="Share">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                </button>
            </div>

            <?php if (count($galleryImages) > 1): ?>
            <div class="pdp-thumbnails" id="pdp-thumbnails">
                <?php foreach ($galleryImages as $gi => $gImg): ?>
                    <?php
                    $thumbUrl = '';
                    if (strpos($gImg, 'http') === 0) {
                        $thumbUrl = $gImg;
                    } else {
                        $thumbUrl = url('assets/uploads/' . $gImg);
                    }
                    ?>
                    <div class="pdp-thumb <?= $gi === 0 ? 'active' : '' ?>" data-full="<?= esc($thumbUrl) ?>" data-index="<?= $gi ?>">
                        <img src="<?= esc($thumbUrl) ?>" alt="<?= esc($product['name']) ?> <?= $gi + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- === INFO COLUMN === -->
        <div class="pdp-info" id="pdp-info">
            <div class="pdp-info__sticky">

                <!-- Brand / Category -->
                <span class="pdp-brand"><?= esc(category_label($product['category'])) ?></span>

                <!-- Title -->
                <h1 class="pdp-title"><?= esc($product['name']) ?></h1>

                <!-- Rating -->
                <div class="pdp-rating">
                    <span class="pdp-rating-stars">
                        <?= str_repeat('★', (int)round($averageRating)) ?><?= str_repeat('☆', 5 - (int)round($averageRating)) ?>
                    </span>
                    <a href="#pdp-reviews-section" class="pdp-rating-count"><?= $averageRating ?> (<?= count($reviews) ?> <?= current_lang() === 'ar' ? 'تقييم' : 'reviews' ?>)</a>
                </div>

                <!-- Price Block -->
                <div class="pdp-price-block">
                    <span class="pdp-price" id="current-price"><?= (int)$product['price'] ?> ج</span>
                    <?php if ($isOfferProduct && $compareAtPrice !== null): ?>
                        <span class="pdp-price-compare" id="old-price"><?= (int)$compareAtPrice ?> ج</span>
                        <span class="pdp-badge pdp-badge--discount" id="save-badge">وفر <?= (int)($compareAtPrice - $product['price']) ?> ج</span>
                    <?php elseif (!$isOfferProduct && $discountPercent > 0): ?>
                        <span class="pdp-price-compare" id="old-price"><?= (int)$originalPrice ?> ج</span>
                        <span class="pdp-badge pdp-badge--discount">-<?= $discountPercent ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Short Description -->
                <?php if (!empty($product['notes'])): ?>
                    <p class="pdp-desc"><?= esc($product['notes']) ?></p>
                <?php endif; ?>

                <!-- Variant Selector -->
                <form action="<?= esc(url('cart.php')) ?>" method="POST" id="product-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <?php if ($variants !== [] && !$isOfferProduct): ?>
                        <?php
                        $initialStock = 0;
                        foreach ($variants as $v) {
                            if ($v['id'] === $selectedVariantId) {
                                $initialStock = (int)$v['stock'];
                                break;
                            }
                        }
                        ?>
                        <div class="pdp-variants">
                            <span class="pdp-variants__label">
                                <?= current_lang() === 'ar' ? 'الحجم' : 'Size' ?>
                                <span class="pdp-badge pdp-badge--stock <?= $initialStock > 5 ? 'in-stock' : ($initialStock > 0 ? 'low-stock' : 'out-of-stock') ?>" id="stock-display">
                                    <?= $initialStock > 5 ? (current_lang() === 'ar' ? 'متوفر' : 'In Stock') : ($initialStock > 0 ? (current_lang() === 'ar' ? 'المتبقي: ' . $initialStock : 'Only ' . $initialStock . ' left') : (current_lang() === 'ar' ? 'غير متوفر' : 'Out of Stock')) ?>
                                </span>
                            </span>
                            <div class="pdp-variants__grid">
                                <?php foreach ($variants as $v): ?>
                                    <?php
                                     $isSel = $v['id'] === $selectedVariantId;
                                     $label = current_lang() === 'ar' ? $v['label_ar'] : $v['label_en'];
                                     $vStock = (int)$v['stock'];
                                     ?>
                                     <button type="button"
                                              class="pdp-variant-btn <?= $isSel ? 'active' : '' ?> <?= $vStock <= 0 ? 'out-of-stock' : '' ?>"
                                              onclick="selectVariant(<?= (int)$v['id'] ?>, <?= (float)$v['price'] ?>, '<?= esc($label) ?>', <?= $vStock ?>)"
                                              data-variant-id="<?= (int)$v['id'] ?>">
                                         <?= esc($label) ?>
                                      </button>
                                  <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" name="variant_id" id="selected-variant-id" value="<?= (int)$selectedVariantId ?>">
                    <?php elseif ($isOfferProduct && $selectedVariantId): ?>
                        <input type="hidden" name="variant_id" id="selected-variant-id" value="<?= (int)$selectedVariantId ?>">
                    <?php endif; ?>

                    <!-- Quantity Selector -->
                    <div class="pdp-qty-row">
                        <span class="pdp-qty-row__label"><?= current_lang() === 'ar' ? 'الكمية' : 'Quantity' ?></span>
                        <div class="pdp-qty">
                            <button type="button" class="pdp-qty__btn" onclick="updateQty(-1)">&minus;</button>
                            <span class="pdp-qty__value" id="qty-display">1</span>
                            <button type="button" class="pdp-qty__btn" onclick="updateQty(1)">&plus;</button>
                        </div>
                        <input type="hidden" name="qty" id="qty-input" value="1">
                    </div>

                    <!-- Actions -->
                    <div class="pdp-actions">
                        <button type="button" class="pdp-btn-cart" onclick="addToCart(this)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            <?= current_lang() === 'ar' ? 'أضف للسلة' : 'Add to Cart' ?>
                        </button>
                        <button type="button" class="pdp-btn-buy" onclick="buyNow(this)">
                            <?= current_lang() === 'ar' ? 'اشتري الآن' : 'Buy Now' ?>
                        </button>
                    </div>
                </form>


                <!-- Accordion (Static & Always Open) -->
                <div class="pdp-accordion" style="border-bottom: 1px solid var(--border-subtle);">
                    <?php if (!empty($product['description'])): ?>
                    <div class="pdp-accordion__item open" style="border-bottom: none; margin-bottom: 1rem;">
                        <div class="pdp-accordion__trigger" style="cursor: default; pointer-events: none; padding-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: #111;"><?= current_lang() === 'ar' ? 'الوصف' : 'Description' ?></span>
                        </div>
                        <div class="pdp-accordion__content" style="max-height: none; overflow: visible;">
                            <div class="pdp-accordion__body" style="padding-top: 0.5rem;">
                                <?php
                                    // Split description by sentence end (dot or semicolon) followed by spaces
                                    $sentences = preg_split('/(?<=[\.;])\s+/', $product['description']);
                                ?>
                                <ul class="pdp-description-list" style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.8rem;">
                                    <?php foreach ($sentences as $sentence):
                                        $sentence = trim($sentence);
                                        if ($sentence === '') continue;
                                        
                                        $sentenceDisplay = esc($sentence);
                                        // Auto-bold specific headers if the sentence starts with them
                                        $prefixes = ['إفتتاحية العطر', 'قلب العطر', 'قاعدة العطر', 'صدر عام', 'Khamrah Lattafa Perfumes', 'Lattafa Perfumes'];
                                        foreach ($prefixes as $pfx) {
                                            if (mb_strpos($sentence, $pfx) === 0) {
                                                $remainder = mb_substr($sentence, mb_strlen($pfx));
                                                $sentenceDisplay = '<strong style="color: #111; font-weight: 700; font-size: 0.98rem;">' . esc($pfx) . '</strong>' . $remainder;
                                                break;
                                            }
                                        }
                                    ?>
                                        <li class="pdp-description-item" style="font-size: 0.95rem; line-height: 1.6; color: #222; font-weight: 500; display: flex; align-items: flex-start; gap: 0.5rem; text-align: justify;">
                                            <span style="color: #c5a059; margin-top: 0.15rem; font-size: 0.85rem; line-height: 1;">✦</span>
                                            <span style="flex: 1;"><?= $sentenceDisplay ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pdp-accordion__item open" style="border-bottom: none; margin-bottom: 1rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                        <div class="pdp-accordion__trigger" style="cursor: default; pointer-events: none; padding-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: #111;"><?= current_lang() === 'ar' ? 'المكونات العطرية' : 'Fragrance Notes' ?></span>
                        </div>
                        <div class="pdp-accordion__content" style="max-height: none; overflow: visible;">
                            <div class="pdp-accordion__body" style="padding-top: 0.5rem; font-size: 0.95rem; color: #222; font-weight: 500; display: flex; align-items: flex-start; gap: 0.5rem;">
                                <span style="color: #c5a059; margin-top: 0.15rem; font-size: 0.85rem; line-height: 1;">✦</span>
                                <span style="flex: 1;">
                                    <?= !empty($product['notes']) ? esc($product['notes']) : (current_lang() === 'ar' ? 'مزيج فاخر من أجود المكونات العطرية.' : 'A luxurious blend of the finest fragrance ingredients.') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pdp-accordion__item open" style="border-bottom: none; margin-bottom: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                        <div class="pdp-accordion__trigger" style="cursor: default; pointer-events: none; padding-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: #111;"><?= current_lang() === 'ar' ? 'معلومات الشحن' : 'Shipping Info' ?></span>
                        </div>
                        <div class="pdp-accordion__content" style="max-height: none; overflow: visible;">
                            <div class="pdp-accordion__body" style="padding-top: 0.5rem; font-size: 0.95rem; color: #222; font-weight: 500; display: flex; align-items: flex-start; gap: 0.5rem;">
                                <span style="color: #c5a059; margin-top: 0.15rem; font-size: 0.85rem; line-height: 1;">✦</span>
                                <span style="flex: 1;">
                                    <?= current_lang() === 'ar'
                                        ? 'يتم التوصيل خلال 2-5 أيام عمل داخل مصر.'
                                        : 'Delivery within 2-5 business days across Egypt.' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ═══════════════ REVIEWS ═══════════════ -->
<section class="pdp-reviews" id="pdp-reviews-section">
    <div class="pdp-reviews__header">
        <div>
            <h2 class="pdp-reviews__title"><?= current_lang() === 'ar' ? 'تقييمات وآراء العملاء' : 'Customer Reviews' ?></h2>
            <div class="pdp-reviews__summary">
                <span class="pdp-reviews__avg"><?= $averageRating ?></span>
                <div>
                    <div class="pdp-reviews__stars"><?= str_repeat('★', (int)round($averageRating)) ?><?= str_repeat('☆', 5 - (int)round($averageRating)) ?></div>
                    <span class="pdp-reviews__meta"><?= current_lang() === 'ar' ? 'بناءً على ' . count($reviews) . ' تقييم' : 'Based on ' . count($reviews) . ' reviews' ?></span>
                </div>
            </div>
        </div>
        <button class="pdp-reviews__write-btn" onclick="document.getElementById('pdp-review-form-box').scrollIntoView({behavior: 'smooth'})">
            <?= current_lang() === 'ar' ? 'اكتب مراجعة' : 'Write a Review' ?>
        </button>
    </div>

    <?php if ($reviewSuccess !== ''): ?>
        <div class="pdp-alert pdp-alert--success">✓ <?= esc($reviewSuccess) ?></div>
    <?php endif; ?>

    <div class="pdp-reviews__grid">
        <div>
            <?php if ($reviews === []): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: #999;">
                    <span style="font-size: 3rem;">⭐</span>
                    <p style="margin-top: 1rem; font-size: 1.05rem;"><?= current_lang() === 'ar' ? 'لا توجد تقييمات لهذا العطر بعد.' : 'No reviews for this fragrance yet.' ?></p>
                    <p style="font-size: 0.85rem; color: #bbb;"><?= current_lang() === 'ar' ? 'كن أول من يشاركنا رأيه وتجربته الفاخرة!' : 'Be the first to share your luxury experience!' ?></p>
                </div>
            <?php else: ?>
                <div class="pdp-reviews__list" id="reviews-list">
                    <?php foreach ($reviews as $i => $rev):
                        $initials = '';
                        $nameParts = explode(' ', trim($rev['customer_name']));
                        foreach ($nameParts as $n) {
                            if (strlen($initials) >= 2) break;
                            $firstChar = mb_substr($n, 0, 1);
                            if ($firstChar !== '') $initials .= mb_strtoupper($firstChar);
                        }
                        ?>
                        <div class="pdp-review-card <?= $i >= 4 ? 'review-hidden' : '' ?>" style="<?= $i >= 4 ? 'display:none;' : '' ?>">
                            <div class="pdp-review-card__header">
                                <div class="pdp-review-card__avatar"><?= esc($initials) ?></div>
                                <div>
                                    <div class="pdp-review-card__name"><?= esc($rev['customer_name']) ?></div>
                                    <span class="pdp-review-card__verified">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                        <?= current_lang() === 'ar' ? 'شراء مؤكد' : 'Verified Purchase' ?>
                                    </span>
                                </div>
                                <span class="pdp-review-card__date"><?= date('Y-m-d', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="pdp-review-card__stars"><?= str_repeat('★', (int)$rev['rating']) ?><?= str_repeat('☆', 5 - (int)$rev['rating']) ?></div>
                            <?php if ($rev['review_text']): ?>
                                <p class="pdp-review-card__text"><?= nl2br(esc($rev['review_text'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($reviews) > 4): ?>
                    <button class="pdp-reviews__show-more" id="reviews-show-more-btn" onclick="showMoreReviews()">
                        <?= current_lang() === 'ar' ? 'عرض المزيد (' . (count($reviews) - 4) . ')' : 'Show More (' . (count($reviews) - 4) . ')' ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pdp-review-form" id="pdp-review-form-box">
            <h3 class="pdp-review-form__title"><?= current_lang() === 'ar' ? 'شاركنا مراجعتك' : 'Write a Review' ?></h3>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="submit_review" value="1">

                <div>
                    <label class="pdp-review-form__label"><?= current_lang() === 'ar' ? 'الاسم بالكامل' : 'Full Name' ?></label>
                    <input type="text" name="customer_name" required class="pdp-review-form__input"
                           placeholder="<?= current_lang() === 'ar' ? 'مثال: سارة أحمد' : 'e.g. Sarah Smith' ?>">
                </div>

                <div>
                    <label class="pdp-review-form__label"><?= current_lang() === 'ar' ? 'تقييمك' : 'Your Rating' ?></label>
                    <div class="pdp-star-select" id="star-selector">
                        <span data-val="1">☆</span>
                        <span data-val="2">☆</span>
                        <span data-val="3">☆</span>
                        <span data-val="4">☆</span>
                        <span data-val="5">☆</span>
                    </div>
                    <input type="hidden" name="rating" id="review-rating-val" value="5">
                </div>

                <div>
                    <label class="pdp-review-form__label"><?= current_lang() === 'ar' ? 'مراجعتك (اختياري)' : 'Your Review (Optional)' ?></label>
                    <textarea name="review_text" rows="4" class="pdp-review-form__textarea"
                              placeholder="<?= current_lang() === 'ar' ? 'اكتب رأيك وتجربتك الفاخرة مع هذا العطر هنا...' : 'Write your experience with this premium fragrance here...' ?>"></textarea>
                </div>

                <button type="submit" class="pdp-review-form__submit">
                    <?= current_lang() === 'ar' ? 'نشر التقييم' : 'Publish Review' ?>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ═══════════════ RELATED PRODUCTS ═══════════════ -->
<?php if (!empty($related)): ?>
<section class="pdp-related">
    <h2 class="pdp-related__title"><?= current_lang() === 'ar' ? 'منتجات قد تعجبك' : 'You May Also Like' ?></h2>
    <div class="pdp-related__grid">
        <?php foreach ($related as $p): ?>
            <?php $showBestseller = false; require __DIR__ . '/includes/partials/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Mobile Sticky Add to Cart Bar -->
<div class="pdp-sticky-bar" id="pdp-sticky-bar">
    <span class="pdp-sticky-bar__price"><?= (int)$product['price'] ?> ج</span>
    <button class="pdp-sticky-bar__btn" onclick="document.querySelector('.pdp-btn-cart').scrollIntoView({behavior:'smooth'}); document.querySelector('.pdp-btn-cart').click();">
        <?= current_lang() === 'ar' ? 'أضف للسلة' : 'Add to Cart' ?>
    </button>
</div>

<script>
(function() {
    let currentPrice = <?= (float)$product['price'] ?>;
    let discountPercent = <?= (float)$discountPercent ?>;

    // ── Sticky Bar ──
    const stickyBar = document.getElementById('pdp-sticky-bar');
    const infoSection = document.getElementById('pdp-info');
    const stickyPriceEl = document.querySelector('.pdp-sticky-bar__price');
    const stickyBtn = document.querySelector('.pdp-sticky-bar__btn');

    function updateStickyBar() {
        if (!stickyBar) return;
        // Always show sticky bar on small screens
        if (window.innerWidth <= 768) {
            stickyBar.classList.add('visible');
            return;
        }
        if (!infoSection) return;
        const rect = infoSection.getBoundingClientRect();
        if (rect.bottom < 0) {
            stickyBar.classList.add('visible');
        } else {
            stickyBar.classList.remove('visible');
        }
    }
    window.addEventListener('scroll', updateStickyBar, { passive: true });
    window.addEventListener('resize', updateStickyBar);
    setTimeout(updateStickyBar, 60);

    function updateStickyPrice() {
        if (!stickyPriceEl) return;
        const qtyInput = document.getElementById('qty-input');
        const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value) || 1) : 1;
        stickyPriceEl.innerText = Math.round(currentPrice * qty) + ' ج';
    }

    if (stickyBtn) {
        stickyBtn.addEventListener('click', function (e) {
            const cartBtn = document.querySelector('.pdp-btn-cart');
            if (cartBtn) {
                cartBtn.click();
                return;
            }
            const form = document.getElementById('product-form');
            if (form) form.submit();
        });
    }

    // ── Thumbnail Click ──
    const thumbnails = document.querySelectorAll('.pdp-thumb');
    const mainImage = document.getElementById('pdp-main-image');
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const fullUrl = this.getAttribute('data-full');
            if (mainImage && fullUrl) {
                mainImage.src = fullUrl;
            }
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ── Accordion ──
    window.toggleAccordion = function(btn) {
        const item = btn.parentElement;
        item.classList.toggle('open');
    };

    // ── Quantity ──
    window.updateQty = function(delta) {
        const input = document.getElementById('qty-input');
        const display = document.getElementById('qty-display');
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        if (val > 99) val = 99;
        input.value = val;
        display.innerText = val;
        updateStickyPrice();
    };

    // ── Variant Select ──
    window.selectVariant = function(id, price, label, stock) {
        document.querySelectorAll('.pdp-variant-btn').forEach(b => b.classList.remove('active'));
        const btn = document.querySelector(`[data-variant-id="${id}"]`);
        if (btn) btn.classList.add('active');
        document.getElementById('selected-variant-id').value = id;

        currentPrice = price;
        const originalPrice = price / (1 - (discountPercent / 100));
        const savings = originalPrice - price;

        document.getElementById('current-price').innerText = Math.round(price) + ' ج';
        const oldPriceEl = document.getElementById('old-price');
        if (oldPriceEl) oldPriceEl.innerText = Math.round(originalPrice) + ' ج';
        const saveBadgeEl = document.getElementById('save-badge');
        if (saveBadgeEl) saveBadgeEl.innerText = 'وفر ' + Math.round(savings) + ' ج';

        const stockDisplay = document.getElementById('stock-display');
        if (stockDisplay) {
            stockDisplay.classList.remove('in-stock', 'low-stock', 'out-of-stock');
            if (stock > 5) {
                stockDisplay.innerText = '<?= esc(current_lang() === 'ar' ? 'متوفر' : 'In Stock') ?>';
                stockDisplay.classList.add('in-stock');
            } else if (stock > 0) {
                stockDisplay.innerText = '<?= esc(current_lang() === 'ar' ? 'المتبقي: ' : 'Only ') ?>' + stock + '<?= esc(current_lang() === 'ar' ? '' : ' left') ?>';
                stockDisplay.classList.add('low-stock');
            } else {
                stockDisplay.innerText = '<?= esc(current_lang() === 'ar' ? 'غير متوفر' : 'Out of Stock') ?>';
                stockDisplay.classList.add('out-of-stock');
            }
        }

        // Update sticky bar price (keep qty in account)
        updateStickyPrice();
    };

    // ── Add to Cart (AJAX) ──
    window.addToCart = function(btn) {
        const form = document.getElementById('product-form');
        const formData = new URLSearchParams();
        formData.append('action', 'add');
        formData.append('product_id', form.querySelector('[name="product_id"]').value);
        formData.append('qty', document.getElementById('qty-input').value);
        const v = form.querySelector('[name="variant_id"]');
        if (v) formData.append('variant_id', v.value);
        var csrfEl = form.querySelector('[name="csrf_token"]');
        formData.append('csrf_token', window.CSRF_TOKEN || (csrfEl ? csrfEl.value : ''));

        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '...';

        const actionUrl = form.getAttribute('action');
        fetch(actionUrl + (actionUrl.indexOf('?') > -1 ? '&' : '?') + 'ajax=1', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(r => r.json()).then((data) => {
            if (data.success) {
                if (data.women_message && typeof window.showScreenToast === 'function') {
                    window.showScreenToast(data.women_message);
                }
                btn.innerHTML = '✓';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = oldHtml;
                }, 2000);
                const badges = document.querySelectorAll('.cart-badge');
                const qty = parseInt(document.getElementById('qty-input').value) || 1;
                badges.forEach(b => {
                    b.textContent = (parseInt(b.textContent) || 0) + qty;
                    b.style.display = 'flex';
                });
                if (typeof openCartDrawer === 'function') {
                    openCartDrawer();
                }
            } else {
                form.submit();
            }
        }).catch(() => {
            form.submit();
        });
    };

    // ── Buy Now ──
    window.buyNow = function(btn) {
        const form = document.getElementById('product-form');
        const formData = new URLSearchParams();
        formData.append('action', 'add');
        formData.append('product_id', form.querySelector('[name="product_id"]').value);
        formData.append('qty', document.getElementById('qty-input').value);
        const v = form.querySelector('[name="variant_id"]');
        if (v) formData.append('variant_id', v.value);
        var csrfEl2 = form.querySelector('[name="csrf_token"]');
        formData.append('csrf_token', window.CSRF_TOKEN || (csrfEl2 ? csrfEl2.value : ''));

        btn.disabled = true;
        btn.textContent = '...';

        const actionUrl = form.getAttribute('action');
        fetch(actionUrl + (actionUrl.indexOf('?') > -1 ? '&' : '?') + 'ajax=1', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(r => r.json()).then((data) => {
            if (data.women_message && typeof window.showScreenToast === 'function') {
                window.showScreenToast(data.women_message);
            }
            window.location.href = '<?= esc(url('checkout.php')) ?>';
        }).catch(() => {
            form.submit();
        });
    };

    // ── Star Rating Selector ──
    const stars = document.querySelectorAll('#star-selector span');
    const ratingInput = document.getElementById('review-rating-val');
    function updateStarsDisplay(val) {
        stars.forEach(star => {
            const starVal = parseInt(star.getAttribute('data-val'));
            if (starVal <= val) {
                star.textContent = '★';
                star.style.color = '#c5a059';
            } else {
                star.textContent = '☆';
                star.style.color = '#ddd';
            }
        });
    }
    updateStarsDisplay(5);

    stars.forEach(star => {
        star.addEventListener('click', () => {
            const val = parseInt(star.getAttribute('data-val'));
            ratingInput.value = val.toString();
            updateStarsDisplay(val);
        });
        star.addEventListener('mouseover', () => {
            const val = parseInt(star.getAttribute('data-val'));
            updateStarsDisplay(val);
        });
    });

    const selector = document.getElementById('star-selector');
    if (selector) {
        selector.addEventListener('mouseleave', () => {
            updateStarsDisplay(parseInt(ratingInput.value));
        });
    }

    // ── Show More Reviews ──
    window.showMoreReviews = function() {
        const hidden = document.querySelectorAll('.pdp-review-card.review-hidden');
        hidden.forEach((el, i) => {
            setTimeout(() => {
                el.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(10px)';
                el.style.display = 'block';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    });
                });
                el.classList.remove('review-hidden');
            }, i * 80);
        });
        const btn = document.getElementById('reviews-show-more-btn');
        if (btn) {
            setTimeout(() => { btn.style.display = 'none'; }, hidden.length * 80 + 200);
        }
        if (hidden.length > 0) {
            setTimeout(() => {
                hidden[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
        if (scrollBtn) {
            scrollBtn.classList.add('visible');
        }
    };
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
