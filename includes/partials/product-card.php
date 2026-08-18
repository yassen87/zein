<?php
declare(strict_types=1);
/** @var array $p product row (localized) */
/** @var bool $showBestseller */
/** @var float|null $offerPrice optional price override from bundle */

$showBestseller = $showBestseller ?? true;
$overridePrice  = $offerPrice ?? null;

$variants  = get_product_variants($p['id']);
$minPrice  = $overridePrice ?? ($p['price'] ?? 0);
$hasMultiplePrices = false;
$firstVariant = null;

if ($overridePrice === null && count($variants) > 0) {
    $firstVariant = $variants[0];
    $prices   = array_column($variants, 'price');
    $minPrice = min($prices);
    $maxPrice = max($prices);
    if ($minPrice !== $maxPrice) {
        $hasMultiplePrices = true;
    }
}

$isAr = current_lang() === 'ar';
$hasOffer = !empty($p['is_offer']) || $overridePrice !== null || in_array('offers', $p['categories'] ?? [$p['category']], true);
?>
<article class="product-card" data-product-id="<?= (int)$p['id'] ?>">
    <div class="product-card__inner">

        <!-- Media Area -->
        <div class="product-card__media">
            <!-- Badges -->
        <div class="product-card__badges">
            <?php if ($hasOffer): ?>
                <div class="product-card__badge product-card__badge--discount">
                    <?= $isAr ? 'عرض' : 'Sale' ?>
                </div>
            <?php endif; ?>
            <?php if ($showBestseller && !empty($p['bestseller'])): ?>
                <div class="product-card__badge product-card__badge--bestseller">
                    <?= $isAr ? 'الأكثر مبيعاً' : 'Best Seller' ?>
                </div>
            <?php endif; ?>
        </div>

            <!-- Wishlist -->
            <button class="product-card__wishlist" aria-label="<?= $isAr ? 'أضف للمفضلة' : 'Add to wishlist' ?>"
                    onclick="toggleWishlist(event, <?= esc(json_encode([
                        'id'    => $p['id'],
                        'name'  => $p['name'],
                        'price' => $minPrice,
                        'image' => $p['image'],
                        'url'   => url('product.php?id=' . $p['id'])
                    ])) ?>)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </button>

            <!-- Product Image Link -->
            <a href="<?= esc(url('product.php?id=' . $p['id'])) ?>" class="product-card__link" aria-label="<?= esc($p['name']) ?>">
                <div class="product-card__image-wrap">
                    <div class="product-card__image"
                         role="img"
                         aria-label="<?= esc($p['name']) ?>"
                         <?= product_image_style($p['image']) ?>>
                    </div>
                </div>
                <!-- Hover overlay -->
                <div class="product-card__overlay">
                    <span class="product-card__view-text"><?= $isAr ? 'عرض المنتج' : 'Quick View' ?></span>
                </div>
            </a>
        </div>

        <!-- Content -->
        <a href="<?= esc(url('product.php?id=' . $p['id'])) ?>" style="text-decoration:none; color:inherit; flex:1; display:flex; flex-direction:column;">
            <div class="product-card__content">
                <!-- Brand/Category -->
                <span class="product-card__brand">
                    <?= esc(category_label($p['category'])) ?>
                </span>

                <!-- Name -->
                <h3 class="product-card__title"><?= esc($p['name']) ?></h3>

                <!-- Rating -->
                <?php if (isset($p['average_rating']) && (int)($p['reviews_count'] ?? 0) > 0): ?>
                    <div class="product-card__rating">
                        <span class="product-card__stars"><?= str_repeat('★', (int)round($p['average_rating'])) ?><?= str_repeat('☆', 5 - (int)round($p['average_rating'])) ?></span>
                        <span class="product-card__rating-count">(<?= (int)$p['reviews_count'] ?>)</span>
                    </div>
                <?php endif; ?>

                <!-- Variant sizes -->
                <?php 
                $displayVariants = array_filter($variants, function($v) {
                    $lblEn = strtolower(trim($v['label_en']));
                    $lblAr = trim($v['label_ar']);
                    return $lblEn !== 'original' && $lblAr !== 'الأصلي';
                });
                if (!$hasOffer && count($displayVariants) > 0 && $overridePrice === null): 
                ?>
                    <div class="product-card__sizes">
                        <?php foreach (array_slice($displayVariants, 0, 3) as $v): ?>
                            <span class="product-card__size-pill">
                                <?php
                                    $lbl = $isAr ? ($v['label_ar'] ?: $v['label_en']) : $v['label_en'];
                                    if ($isAr) {
                                        // Automatically translate 'ml' or 'ML' to 'مل' for cleaner Arabic display
                                        $lbl = str_ireplace(['ml', ' ml'], ['مل', ' مل'], $lbl);
                                    }
                                    echo esc($lbl);
                                ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($displayVariants) > 3): ?>
                            <span class="product-card__size-pill product-card__size-pill--more">+<?= count($displayVariants) - 3 ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="product-card__price-row">
                    <span class="product-card__price">
                        <?php if ($hasMultiplePrices && $overridePrice === null): ?>
                            <?= esc(format_price($minPrice)) ?> - <?= esc(format_price($maxPrice)) ?>
                        <?php else: ?>
                            <?= esc(format_price($minPrice)) ?>
                        <?php endif; ?>
                    </span>
                    <?php if ($hasOffer && isset($p['compare_at_price']) && $p['compare_at_price'] > $minPrice): ?>
                        <span class="product-card__compare"><?= esc(format_price($p['compare_at_price'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>

        <!-- Add to Cart -->
        <div class="product-card__footer">
            <button type="button"
                    class="btn-quick-add btn-quick-configure"
                    data-product="<?= base64_encode(json_encode($p + ['variants' => $variants])) ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span><?= $isAr ? 'أضف للسلة' : 'Add to Cart' ?></span>
            </button>
        </div>

    </div>
</article>