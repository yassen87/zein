document.addEventListener('DOMContentLoaded', function() {
    var uiSubtotal = document.getElementById('ui-subtotal');
    if (!uiSubtotal) return;

    var citySelect = document.getElementById('checkout_city_id');
    var uiShipping = document.getElementById('ui-shipping');
    var uiTotal = document.getElementById('ui-total');
    var discountRow = document.getElementById('row-discount');
    var uiDiscount = document.getElementById('ui-discount');
    var subtotal = parseFloat(uiSubtotal.getAttribute('data-val')) || 0;
    var discountPercentage = 0;
    var currency = document.querySelector('meta[name="currency"]')
        ? document.querySelector('meta[name="currency"]').getAttribute('content')
        : 'ج.م.';

    function updateTotals() {
        var cost = 0;
        if (citySelect && citySelect.selectedIndex >= 0) {
            var opt = citySelect.options[citySelect.selectedIndex];
            cost = parseFloat(opt.getAttribute('data-cost')) || 0;
            if (!isNaN(cost) && cost > 0) {
                if (uiShipping) uiShipping.innerHTML = '<strong>' + cost.toFixed(2) + ' ' + currency + '</strong>';
            } else {
                if (uiShipping) uiShipping.innerHTML = '<strong>\u2014</strong>';
            }
        } else {
            if (uiShipping) uiShipping.innerHTML = '<strong>\u2014</strong>';
        }

        var discountAmt = subtotal * (discountPercentage / 100);
        if (discountAmt > 0) {
            if (discountRow) discountRow.style.display = 'flex';
            if (uiDiscount) uiDiscount.innerHTML = '<strong>-' + discountAmt.toFixed(2) + ' ' + currency + '</strong>';
        } else {
            if (discountRow) discountRow.style.display = 'none';
        }

        if (uiTotal) uiTotal.innerHTML = (subtotal - discountAmt + cost).toFixed(2) + ' ' + currency;
    }

    function updateCartAjax(lineKey, action, newQty) {
        var fd = new FormData();
        fd.append('line_key', lineKey);
        fd.append('action', action);
        var csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) fd.append('csrf_token', csrfInput.value);
        if (newQty !== undefined) fd.append('qty', newQty);
        var hiddenPromoField = document.getElementById('applied_promo_code');
        if (hiddenPromoField && hiddenPromoField.value) fd.append('promo_code', hiddenPromoField.value);

        var ajaxUrl = window.appUrl ? window.appUrl('ajax_update_cart.php') : (window.BASE_URL ? window.BASE_URL + 'ajax_update_cart.php' : 'ajax_update_cart.php');

        fetch(ajaxUrl, {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (data.new_qty > 0) {
                    var row = document.querySelector('.neo-qty-controls[data-line-key="' + lineKey + '"]');
                    if (row) {
                        var input = row.querySelector('.neo-qty-input');
                        if (input) input.value = data.new_qty;
                    }
                    var priceEl = document.querySelector('[data-line-price-key="' + lineKey + '"]');
                    if (priceEl) {
                        priceEl.innerText = data.line_total;
                    }
                } else {
                    var btn = document.querySelector('.neo-qty-controls[data-line-key="' + lineKey + '"]');
                    if (btn) {
                        var itemRow = btn.closest('.neo-product');
                        if (itemRow) itemRow.remove();
                    }
                    if (data.cart_count === 0) location.reload();
                }

                subtotal = data.subtotal;
                if (uiSubtotal) uiSubtotal.innerText = data.subtotal_formatted;
                updateTotals();

                document.querySelectorAll('.cart-badge').forEach(function(b) {
                    b.textContent = data.cart_count;
                    b.style.display = data.cart_count > 0 ? 'flex' : 'none';
                });
            }
        });
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-qty-plus, .btn-qty-minus, .btn-remove-item');
        if (!btn) return;

        var container = btn.closest('.neo-qty-controls');
        if (!container) return;
        var lineKey = container.getAttribute('data-line-key');
        var input = container.querySelector('.neo-qty-input');
        var currentQty = parseInt(input ? input.value : '0');

        if (btn.classList.contains('btn-qty-plus')) {
            updateCartAjax(lineKey, 'update_qty', currentQty + 1);
        } else if (btn.classList.contains('btn-qty-minus')) {
            if (currentQty > 1) {
                updateCartAjax(lineKey, 'update_qty', currentQty - 1);
            } else {
                updateCartAjax(lineKey, 'remove');
            }
        } else if (btn.classList.contains('btn-remove-item')) {
            updateCartAjax(lineKey, 'remove');
        }
    });

    var btnApplyPromo = document.getElementById('btn-apply-promo');
    var promoInput = document.getElementById('promo-code-input');
    var promoMessage = document.getElementById('promo-message');
    var hiddenPromoField = document.getElementById('applied_promo_code');
    var togglePromoBtn = document.getElementById('toggle-promo-btn');
    var promoSection = document.getElementById('promo-input-section');

    if (togglePromoBtn && promoSection) {
        togglePromoBtn.addEventListener('click', function() {
            promoSection.style.display = 'flex';
            togglePromoBtn.parentElement.style.display = 'none';
        });
    }

    if (btnApplyPromo && promoInput) {
        btnApplyPromo.addEventListener('click', function() {
            var code = promoInput.value.trim();
            if (code === '') return;

            btnApplyPromo.disabled = true;
            btnApplyPromo.innerText = '...';

            var fd = new FormData();
            fd.append('code', code);
            var csrfInput = document.querySelector('input[name="csrf_token"]');
            if (csrfInput) fd.append('csrf_token', csrfInput.value);

            var ajaxPromoUrl = window.appUrl ? window.appUrl('ajax_apply_promo.php') : (window.BASE_URL ? window.BASE_URL + 'ajax_apply_promo.php' : 'ajax_apply_promo.php');

            fetch(ajaxPromoUrl, {
                method: 'POST',
                body: fd
            }).then(function(r) { return r.json(); }).then(function(data) {
                btnApplyPromo.disabled = false;
                btnApplyPromo.innerText = 'Apply';

                if (data.error) {
                    if (promoMessage) promoMessage.style.color = '#ef4444';
                    if (promoMessage) promoMessage.innerText = data.error;
                    discountPercentage = 0;
                    if (hiddenPromoField) hiddenPromoField.value = '';
                } else if (data.success) {
                    if (promoMessage) promoMessage.style.color = '#10b981';
                    if (promoMessage) promoMessage.innerText = 'Promo applied! ' + data.discount_percentage + '%';
                    discountPercentage = data.discount_percentage;
                    if (hiddenPromoField) hiddenPromoField.value = code;
                }
                updateTotals();
            }).catch(function() {
                btnApplyPromo.disabled = false;
                btnApplyPromo.innerText = 'Apply';
                if (promoMessage) promoMessage.style.color = '#ef4444';
                if (promoMessage) promoMessage.innerText = 'Error applying promo code.';
            });
        });

        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnApplyPromo.click();
            }
        });
    }

    if (citySelect) {
        citySelect.addEventListener('change', updateTotals);
    }
    updateTotals();
});
