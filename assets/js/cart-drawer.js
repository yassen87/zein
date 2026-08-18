window.BASE_URL = window.BASE_URL || '';
window.appUrl = window.appUrl || function(path) {
    var base = window.BASE_URL;
    if (base && !base.endsWith('/')) base += '/';
    return base + String(path || '').replace(/^\/+/, '');
};

function openCartDrawer() {
    var overlay = document.getElementById('cart-drawer-overlay');
    var drawer = document.getElementById('cart-drawer');
    if (overlay) overlay.classList.add('is-active');
    if (drawer) drawer.classList.add('is-active');
    loadCartDrawerItems();
}

function closeCartDrawer() {
    var overlay = document.getElementById('cart-drawer-overlay');
    var drawer = document.getElementById('cart-drawer');
    if (overlay) overlay.classList.remove('is-active');
    if (drawer) drawer.classList.remove('is-active');
}

function escapeCartText(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[ch];
    });
}

function loadCartDrawerItems() {
    fetch(window.appUrl('ajax_get_cart.php'))
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var container = document.getElementById('cart-drawer-items');
        if (!container) return;
        var isAr = document.documentElement.lang === 'ar';
        var subtotalEl = document.getElementById('cart-drawer-subtotal-val');
        if (subtotalEl) subtotalEl.textContent = data.subtotal_formatted;

        document.querySelectorAll('.cart-badge').forEach(function(b) {
            b.textContent = data.cart_count;
            b.style.display = data.cart_count > 0 ? 'flex' : 'none';
        });

        if (data.items.length === 0) {
            container.innerHTML = '<div class="cart-drawer__empty">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>' +
                '<p style="font-size:1rem;font-weight:700;color:#999;margin:0 0 0.3rem;">' + (isAr ? 'سلتك فارغة' : 'Your cart is empty') + '</p>' +
                '<p style="font-size:0.82rem;color:#bbb;margin:0;">' + (isAr ? 'أضف منتجاتك المفضلة' : 'Add your favourite items') + '</p>' +
                '</div>';
            return;
        }

        var html = '';
        data.items.forEach(function(item) {
            var name = escapeCartText(item.name);
            var variant = escapeCartText(item.variant);
            var image = escapeCartText(item.image);
            var price = escapeCartText(item.price_formatted);
            var lineKey = encodeURIComponent(String(item.line_key || ''));
            var variantText = item.variant
                ? '<div class="cart-item__variant">' + variant + '</div>' : '';
            var imgHtml = item.image
                ? '<img src="' + image + '" alt="' + name + '" onerror="this.parentElement.innerHTML=\'\\uD83C\\uDF38\'">'
                : '\uD83C\uDF38';
            var removeSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';

            html += '<div class="cart-item">' +
                '<div class="cart-item__image">' + imgHtml + '</div>' +
                '<div class="cart-item__details">' +
                '<h4 class="cart-item__title" title="' + name + '">' + name + '</h4>' +
                variantText +
                '<div class="cart-item__price-qty">' +
                '<div class="cart-item__qty-controls">' +
                '<button class="cart-item__qty-btn" onclick="updateCartDrawerQty(decodeURIComponent(\'' + lineKey + '\'), ' + (item.qty - 1) + ')">\u2212</button>' +
                '<input class="cart-item__qty-input" type="text" readonly value="' + item.qty + '">' +
                '<button class="cart-item__qty-btn" onclick="updateCartDrawerQty(decodeURIComponent(\'' + lineKey + '\'), ' + (item.qty + 1) + ')">+</button>' +
                '</div>' +
                '<span class="cart-item__price">' + price + '</span>' +
                '</div>' +
                '</div>' +
                '<button class="cart-item__remove" onclick="removeCartDrawerItem(decodeURIComponent(\'' + lineKey + '\'))" title="' + (isAr ? 'حذف' : 'Remove') + '">' + removeSvg + '</button>' +
                '</div>';
        });
        container.innerHTML = html;
    });
}

function updateCartDrawerQty(lineKey, newQty) {
    if (newQty < 1) {
        removeCartDrawerItem(lineKey);
        return;
    }
    var params = new URLSearchParams();
    params.append('action', 'update_qty');
    params.append('line_key', lineKey);
    params.append('qty', newQty.toString());
    params.append('csrf_token', window.CSRF_TOKEN || '');

    fetch(window.appUrl('ajax_update_cart.php'), {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            loadCartDrawerItems();
        }
    });
}

function removeCartDrawerItem(lineKey) {
    var params = new URLSearchParams();
    params.append('action', 'remove');
    params.append('line_key', lineKey);
    params.append('csrf_token', window.CSRF_TOKEN || '');

    fetch(window.appUrl('ajax_update_cart.php'), {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            loadCartDrawerItems();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*="cart.php"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openCartDrawer();
        });
    });

    document.body.addEventListener('click', function(e) {
        var bundleBtn = e.target.closest('.hp-quick-add-bundle');
        if (!bundleBtn) return;

        e.preventDefault();
        var bid = bundleBtn.getAttribute('data-bundle-id');
        if (!bid) return;

        bundleBtn.disabled = true;
        var span = bundleBtn.querySelector('span');
        var oldText = span ? span.textContent : '';
        if (span) span.textContent = '...';

        var params = new URLSearchParams();
        params.append('action', 'add');
        params.append('bundle_id', bid);
        params.append('qty', '1');
        params.append('csrf_token', window.CSRF_TOKEN || '');

        fetch(window.appUrl('cart.php?ajax=1'), {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            bundleBtn.disabled = false;
            if (span) span.textContent = '\u2713';
            setTimeout(function() { if (span) span.textContent = oldText; }, 2000);
            if (data.success) {
                fetch(window.appUrl('ajax_get_cart.php'))
                    .then(function(r) { return r.json(); })
                    .then(function(cartData) {
                        document.querySelectorAll('.cart-badge').forEach(function(b) {
                            b.textContent = cartData.cart_count;
                            b.style.display = cartData.cart_count > 0 ? 'flex' : 'none';
                        });
                    }).catch(function() {});
            }
        })
        .catch(function() {
            bundleBtn.disabled = false;
            if (span) span.textContent = oldText;
        });
    });
});
