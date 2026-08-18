<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
$pageTitle = t('bottom_nav_wishlist');
$pageDescription = current_lang() === 'ar'
    ? 'منتجاتك المفضلة محفوظة في مكان واحد لتعود إليها بسهولة.'
    : 'Your saved favorite products in one polished wishlist.';
$extraCss = [
    url('assets/css/pages/wishlist.css?v=' . filemtime(__DIR__ . '/assets/css/pages/wishlist.css'))
];
require __DIR__ . '/includes/header.php';
?>

<div class="wishlist-page">
    <section class="wishlist-hero">
        <span class="wishlist-hero__eyebrow"><?= current_lang() === 'ar' ? 'قائمة مختارة' : 'Curated list' ?></span>
        <h1 class="wishlist-hero__title"><?= esc(t('bottom_nav_wishlist')) ?></h1>
        <p class="wishlist-hero__sub"><?= current_lang() === 'ar' ? 'احتفظ بعطورك المفضلة وقارن بينها قبل الطلب.' : 'Save your favorite fragrances and compare them before checkout.' ?></p>
    </section>

    <section class="wishlist-shell" aria-live="polite">
        <div class="wishlist-toolbar">
            <div>
                <span class="wishlist-toolbar__label"><?= current_lang() === 'ar' ? 'المنتجات المحفوظة' : 'Saved products' ?></span>
                <strong id="wishlist-count" class="wishlist-toolbar__count">0</strong>
            </div>
            <button type="button" id="wishlist-clear" class="wishlist-toolbar__clear">
                <?= current_lang() === 'ar' ? 'مسح الكل' : 'Clear all' ?>
            </button>
        </div>

        <div id="wishlist-empty" class="wishlist-empty" hidden>
            <svg class="wishlist-empty__icon" width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <h2><?= esc(t('wishlist_empty')) ?></h2>
            <p><?= esc(t('wishlist_empty_sub')) ?></p>
            <a href="<?= esc(url('products.php')) ?>" class="wishlist-empty__cta"><?= esc(t('hero_new_cta')) ?></a>
        </div>

        <div id="wishlist-grid" class="wishlist-grid product-grid">
            <!-- JS Populated -->
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currency = <?= json_encode(t('currency')) ?>;
    var fallbackBaseUrl = <?= json_encode(rtrim(get_base_url() . base_path(), '/') . '/') ?>;
    var grid = document.getElementById('wishlist-grid');
    var empty = document.getElementById('wishlist-empty');
    var count = document.getElementById('wishlist-count');
    var clearBtn = document.getElementById('wishlist-clear');

    function getWishlist() {
        try {
            var parsed = JSON.parse(localStorage.getItem('wishlist') || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function resolveAsset(path) {
        if (typeof window.appUrl === 'function') {
            return window.appUrl(path);
        }
        var base = window.BASE_URL || fallbackBaseUrl || '';
        if (base && !base.endsWith('/')) base += '/';
        return base + String(path || '').replace(/^\/+/, '');
    }

    function resolveImageUrl(image) {
        image = String(image || '');
        if (!image) return '';
        if (image.indexOf('http://') === 0 || image.indexOf('https://') === 0) return image;
        if (image.indexOf('.') > -1 || image.indexOf('img_') === 0) return resolveAsset('assets/uploads/' + image);
        if (image !== 'default') return resolveAsset('assets/img/' + image + '.jpg');
        return '';
    }

    function buildWishlistCard(p) {
        var productId = String(p.id || '');
        var card = document.createElement('div');
        card.className = 'product-card wishlist-card';
        card.setAttribute('data-product-id', productId);

        var inner = document.createElement('div');
        inner.className = 'product-card__inner';

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'product-card__wishlist wishlist-card__remove is-active';
        remove.setAttribute('aria-label', <?= json_encode(current_lang() === 'ar' ? 'إزالة من المفضلة' : 'Remove from wishlist') ?>);
        remove.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
        remove.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof toggleWishlist === 'function') {
                toggleWishlist(e, Object.assign({}, p, { id: productId }));
            } else {
                var next = getWishlist().filter(function(item) {
                    return String(item.id) !== productId;
                });
                localStorage.setItem('wishlist', JSON.stringify(next));
            }
            renderWishlist();
        });

        var link = document.createElement('a');
        link.className = 'product-card__link';
        link.href = String(p.url || '#');

        var media = document.createElement('div');
        media.className = 'product-card__media';
        var imageWrap = document.createElement('div');
        imageWrap.className = 'product-card__image-wrap';
        var image = document.createElement('div');
        image.className = 'product-card__image';
        var imageUrl = resolveImageUrl(p.image);
        if (imageUrl) image.style.backgroundImage = 'url("' + imageUrl.replace(/"/g, '%22') + '")';

        var content = document.createElement('div');
        content.className = 'product-card__content';
        var title = document.createElement('h3');
        title.className = 'product-card__title';
        title.textContent = String(p.name || '');
        var priceRow = document.createElement('div');
        priceRow.className = 'product-card__price-row';
        var price = document.createElement('span');
        price.className = 'product-card__price';
        price.textContent = (Number(p.price) || 0).toFixed(2) + ' ' + currency;

        imageWrap.appendChild(image);
        media.appendChild(imageWrap);
        priceRow.appendChild(price);
        content.appendChild(title);
        content.appendChild(priceRow);
        link.appendChild(media);
        link.appendChild(content);
        inner.appendChild(remove);
        inner.appendChild(link);
        card.appendChild(inner);
        return card;
    }

    function renderWishlist() {
        var wishlist = getWishlist();
        count.textContent = wishlist.length.toString();
        clearBtn.hidden = wishlist.length === 0;

        if (wishlist.length === 0) {
            grid.hidden = true;
            empty.hidden = false;
            grid.innerHTML = '';
            return;
        }

        grid.hidden = false;
        empty.hidden = true;
        grid.innerHTML = '';
        wishlist.forEach(function(p) {
            grid.appendChild(buildWishlistCard(p));
        });
    }

    clearBtn.addEventListener('click', function() {
        localStorage.removeItem('wishlist');
        renderWishlist();
    });
    
    renderWishlist();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
