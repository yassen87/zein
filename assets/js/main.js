'use strict';

(function () {
    var modal = document.getElementById('quick-add-modal');

    if (modal) {
        var btns = document.querySelectorAll('.btn-quick-configure');
        var closeBtn = modal.querySelector('.modal-configure__close');
        var imageEl = document.getElementById('modal-image');
        var titleEl = document.getElementById('modal-title');
        var priceDisplay = document.getElementById('modal-price-display');
        var selectedSizeLabel = document.getElementById('selected-variant-label');
        var variantsCont = document.getElementById('modal-variants');
        var idInput = document.getElementById('modal-product-id');
        var qtyInput = document.getElementById('modal-qty');
        var qtyMinus = modal.querySelector('[data-action="minus"]');
        var qtyPlus = modal.querySelector('[data-action="plus"]');

        var currentProduct = null;
        var currentVariantPrice = 0;

        function fmt(num) {
            var isAr = document.documentElement.lang === 'ar';
            var currency = (currentProduct && currentProduct.currency) ? currentProduct.currency : (isAr ? 'ج.م.' : 'EGP');
            return parseFloat(num).toFixed(2) + ' ' + currency;
        }

        function updatePrice() {
            if (!qtyInput || !priceDisplay) return;
            var qty = parseInt(qtyInput.value) || 1;
            var total = currentVariantPrice * qty;
            priceDisplay.textContent = fmt(total);
        }

        function buildVariants(variants) {
            if (!variantsCont) return;
            variantsCont.innerHTML = '';
            if (!variants || !variants.length) return;

            variants.forEach(function (v, i) {
                var isSel = (i === 0);
                var labelStr = v.label_ar || v.label_en || v.label;

                var lbl = document.createElement('label');
                lbl.className = 'variant-pill-modern';

                var inp = document.createElement('input');
                inp.type = 'radio';
                inp.name = 'variant_id';
                inp.value = v.id;
                inp.checked = isSel;

                var content = document.createElement('span');
                content.className = 'pill-content';
                content.textContent = labelStr;

                lbl.appendChild(inp);
                lbl.appendChild(content);
                variantsCont.appendChild(lbl);

                if (isSel) {
                    currentVariantPrice = parseFloat(v.price) || 0;
                    var stockText = '';
                    if (v.stock !== undefined) {
                        stockText = ' (' + (document.documentElement.lang === 'ar' ? 'المتبقي: ' : 'Stock: ') + v.stock + ')';
                    }
                    if (selectedSizeLabel) selectedSizeLabel.textContent = labelStr + stockText;
                }

                inp.addEventListener('change', function () {
                    if (inp.checked) {
                        currentVariantPrice = parseFloat(v.price) || 0;
                        var sText = '';
                        if (v.stock !== undefined) {
                            sText = ' (' + (document.documentElement.lang === 'ar' ? 'المتبقي: ' : 'Stock: ') + v.stock + ')';
                        }
                        if (selectedSizeLabel) selectedSizeLabel.textContent = labelStr + sText;
                        updatePrice();
                    }
                });
            });

            updatePrice();
        }

        btns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                try {
                    var b64Data = btn.getAttribute('data-product');
                    var binary = window.atob(b64Data);
                    var bytes = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) {
                        bytes[i] = binary.charCodeAt(i);
                    }
                    var decodedString = new TextDecoder('utf-8').decode(bytes);
                    var data = JSON.parse(decodedString);
                    currentProduct = data;

                    if (titleEl) titleEl.textContent = data.name;

                    var placeholderEl = document.getElementById('modal-image-placeholder');

                    function showImg(url) {
                        if (!imageEl) return;
                        imageEl.src = url;
                        imageEl.style.display = 'block';
                        imageEl.style.width = '100%';
                        imageEl.style.height = '100%';
                        imageEl.style.objectFit = 'contain';
                        if (placeholderEl) placeholderEl.style.display = 'none';
                    }
                    function hideImg() {
                        if (!imageEl) return;
                        imageEl.src = '';
                        imageEl.style.display = 'none';
                        if (placeholderEl) placeholderEl.style.display = 'block';
                    }

                    if (data.image) {
                        var baseUrl = window.BASE_URL || '';
                        if (baseUrl && !baseUrl.endsWith('/')) baseUrl += '/';

                        var pathsToTry = [];
                        if (data.image.startsWith('http')) {
                            pathsToTry.push(data.image);
                        } else {
                            pathsToTry.push(baseUrl + 'assets/uploads/' + data.image);
                            pathsToTry.push('assets/uploads/' + data.image);

                            if (!data.image.includes('.')) {
                                pathsToTry.push(baseUrl + 'assets/img/' + data.image + '.jpg');
                                pathsToTry.push('assets/img/' + data.image + '.jpg');
                            }
                        }

                        var tryLoad = function(idx) {
                            if (idx >= pathsToTry.length || !imageEl) {
                                hideImg();
                                return;
                            }

                            var url = pathsToTry[idx];
                            imageEl.onload = function() {
                                imageEl.style.display = 'block';
                                imageEl.style.width = '100%';
                                imageEl.style.height = '100%';
                                imageEl.style.objectFit = 'contain';
                                if (placeholderEl) placeholderEl.style.display = 'none';
                            };
                            imageEl.onerror = function() {
                                tryLoad(idx + 1);
                            };
                            imageEl.src = url;
                        };
                        tryLoad(0);
                    } else {
                        hideImg();
                    }

                    if (idInput) idInput.value = data.id;
                    if (qtyInput) qtyInput.value = 1;
                    buildVariants(data.variants);
                    modal.showModal();
                } catch (err) {
                    console.error('Failed to parse product data', err);
                }
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () { modal.close(); });
        }
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

        if (qtyMinus) {
            qtyMinus.addEventListener('click', function () {
                if (!qtyInput) return;
                var v = parseInt(qtyInput.value) || 1;
                if (v > 1) { qtyInput.value = v - 1; updatePrice(); }
            });
        }

        if (qtyPlus) {
            qtyPlus.addEventListener('click', function () {
                if (!qtyInput) return;
                var v = parseInt(qtyInput.value) || 1;
                if (v < 99) { qtyInput.value = v + 1; updatePrice(); }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                var v = parseInt(qtyInput.value) || 1;
                if (v < 1) v = 1; if (v > 99) v = 99;
                qtyInput.value = v;
                updatePrice();
            });
        }

        var form = document.getElementById('modal-form');
        if (form) {
            var addBtn = form.querySelector('.modal-add-btn');
            var checkoutBtn = form.querySelector('.modal-checkout-btn');

            function handleCartAction(isCheckout) {
                var formData = new URLSearchParams();
                formData.append('action', 'add');
                var csrfEl = form.querySelector('[name="csrf_token"]');
                formData.append('csrf_token', window.CSRF_TOKEN || (csrfEl ? csrfEl.value : ''));

                var qtyEl = document.getElementById('modal-qty');
                var qty = qtyEl ? qtyEl.value : '1';
                formData.append('qty', qty);

                var pidEl = document.getElementById('modal-product-id');
                var pid = pidEl ? pidEl.value : '';
                formData.append('product_id', pid);

                var variantsContEl = document.getElementById('modal-variants');
                if (variantsContEl) {
                    var variantEl = variantsContEl.querySelector('input[name="variant_id"]:checked');
                    if (variantEl) {
                        formData.append('variant_id', variantEl.value);
                    }
                }

                var actionUrl = form.getAttribute('action');
                if (isCheckout) {
                    if (checkoutBtn) {
                        checkoutBtn.disabled = true;
                        checkoutBtn.textContent = '...';
                    }
                    fetch(actionUrl + (actionUrl.indexOf('?') > -1 ? '&' : '?') + 'ajax=1', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                    }).then(function() {
                        window.location.href = window.appUrl ? window.appUrl('checkout.php') : ((window.BASE_URL || '') + 'checkout.php');
                    }).catch(function() {
                        form.submit();
                    });
                } else {
                    if (addBtn) {
                        addBtn.disabled = true;
                        var oldText = addBtn.textContent;
                        addBtn.textContent = '...';

                        fetch(actionUrl + (actionUrl.indexOf('?') > -1 ? '&' : '?') + 'ajax=1', {
                            method: 'POST',
                            body: formData,
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                        }).then(function(r) { return r.json(); }).then(function(data) {
                            addBtn.disabled = false;
                            addBtn.textContent = '\u2713 \u062A\u0645';
                            setTimeout(function() { addBtn.textContent = oldText; }, 2000);

                            if (data.success) {
                                if (data.women_message && typeof window.showScreenToast === 'function') {
                                    window.showScreenToast(data.women_message);
                                }
                                modal.close();
                                fetch(window.appUrl ? window.appUrl('ajax_get_cart.php') : ((window.BASE_URL || '') + 'ajax_get_cart.php'))
                                    .then(function(r){ return r.json(); })
                                    .then(function(cartData){
                                        document.querySelectorAll('.cart-badge').forEach(function(b){
                                            b.textContent = cartData.cart_count;
                                            b.style.display = cartData.cart_count > 0 ? 'flex' : 'none';
                                        });
                                    }).catch(function(){});
                            } else {
                                form.submit();
                            }
                        }).catch(function() {
                            form.submit();
                        });
                    }
                }
            }

            if (addBtn) {
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleCartAction(false);
                });
            }

            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleCartAction(true);
                });
            }
        }
    }

    var navToggle = document.querySelector('.nav-toggle');
    var siteNav = document.getElementById('site-nav');
    if (navToggle && siteNav) {
        navToggle.addEventListener('click', function () {
            siteNav.classList.toggle('is-open');
        });
    }

    var themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var isDaylight = document.documentElement.classList.toggle('daylight');
            try {
                localStorage.setItem('medal-theme', isDaylight ? 'light' : 'dark');
            } catch (e) {}
        });
    }

    window.showScreenToast = function(message) {
        if (!message) return;
        var container = document.getElementById('screen-toast-container');
        if (!container) return;
        container.innerHTML = '';

        var toast = document.createElement('div');
        toast.className = 'screen-toast';
        toast.setAttribute('role', 'status');

        var text = document.createElement('p');
        text.textContent = message;
        toast.appendChild(text);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'screen-toast__close';
        closeBtn.setAttribute('aria-label', document.documentElement.lang === 'ar' ? 'إغلاق' : 'Dismiss');
        closeBtn.textContent = '×';
        closeBtn.addEventListener('click', function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
        toast.appendChild(closeBtn);

        container.appendChild(toast);
        window.setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    };

    document.addEventListener('click', function(event) {
        if (event.target.matches('.screen-toast__close')) {
            var toast = event.target.closest('.screen-toast');
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }
    });

    window.toggleWishlist = function(e, product) {
        e.preventDefault();
        e.stopPropagation();

        var wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        var index = wishlist.findIndex(function(item) { return item.id === product.id; });
        var btn = e.currentTarget;

        if (index > -1) {
            wishlist.splice(index, 1);
            if (btn) btn.classList.remove('is-active');
        } else {
            wishlist.push(product);
            if (btn) btn.classList.add('is-active');
        }

        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        updateWishlistBadges();
    };

    function updateWishlistBadges() {
        var wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        var count = wishlist.length;
        document.querySelectorAll('.wishlist-badge').forEach(function(badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    function syncWishlistUI() {
        var wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        var ids = wishlist.map(function(item) { return item.id; });

        document.querySelectorAll('.product-card, .product-detail-copy').forEach(function(el) {
            var pid = parseInt(el.getAttribute('data-product-id'));
            if (ids.includes(pid)) {
                var btn = el.querySelector('.product-card__wishlist');
                if (btn) btn.classList.add('is-active');
            }
        });
        updateWishlistBadges();
    }

    syncWishlistUI();

    // Scroll to top button
    (function() {
        var btn = document.getElementById('scroll-top');
        if (!btn) return;

        var ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    if (window.scrollY > 400) {
                        btn.classList.add('visible');
                    } else {
                        btn.classList.remove('visible');
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        btn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
})();

// Live search
(function() {
    var containers = document.querySelectorAll('.search-box-container');
    
    containers.forEach(function(container) {
        var searchInput = container.querySelector('.header-search__input');
        var searchDropdown = container.querySelector('.header-search__dropdown');
        var searchResults = container.querySelector('.search-results');
        var searchViewAll = container.querySelector('.search-view-all');
        var searchClear = container.querySelector('.header-search__clear');
        
        if (!searchInput || !searchDropdown) return;

        var debounceTimer;
        var currentQuery = '';

        function escHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function syncClearButton() {
            if (!searchClear) return;
            searchClear.classList.toggle('is-visible', searchInput.value.trim().length > 0);
        }

        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            currentQuery = query;
            syncClearButton();

            clearTimeout(debounceTimer);

            if (query.length < 2) {
                searchDropdown.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(function() {
                fetch((window.appUrl ? window.appUrl('ajax_search.php') : (window.BASE_URL + 'ajax_search.php')) + '?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (query !== currentQuery) return;

                        if (data.results && data.results.length > 0) {
                            var html = '';
                            data.results.forEach(function(item) {
                                html += '<a href="' + (window.appUrl ? window.appUrl('product.php') : (window.BASE_URL + 'product.php')) + '?id=' + item.id + '" class="search-results__item">';
                                if (item.image) {
                                    html += '<img src="' + (window.appUrl ? window.appUrl('assets/uploads/' + item.image) : (window.BASE_URL + 'assets/uploads/' + item.image)) + '" class="search-results__item-img" alt="" loading="lazy">';
                                }
                                html += '<div class="search-results__item-info">';
                                html += '<div class="search-results__item-name">' + escHtml(item.name) + '</div>';
                                html += '<div class="search-results__item-price">' + item.price + ' ' + (document.documentElement.lang === 'ar' ? 'جنيه' : 'EGP') + '</div>';
                                if (item.category) html += '<div class="search-results__item-cat">' + escHtml(item.category) + '</div>';
                                html += '</div></a>';
                            });
                            searchResults.innerHTML = html;
                            searchViewAll.href = (window.appUrl ? window.appUrl('search.php') : (window.BASE_URL + 'search.php')) + '?q=' + encodeURIComponent(query);
                            searchDropdown.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div class="search-results__empty">' + (document.documentElement.lang === 'ar' ? 'لا توجد نتائج' : 'No results found') + '</div>';
                            searchViewAll.href = '#';
                            searchDropdown.style.display = 'block';
                        }
                    })
                    .catch(function() {
                        searchDropdown.style.display = 'none';
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = 'none';
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchDropdown.style.display = 'none';
                this.blur();
            }
        });

        if (searchClear) {
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                currentQuery = '';
                clearTimeout(debounceTimer);
                searchDropdown.style.display = 'none';
                syncClearButton();
                searchInput.focus();
            });
        }

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                this.dispatchEvent(new Event('input'));
            }
        });

        syncClearButton();
    });
})();

// Skeleton loading for product cards
(function() {
    var cards = document.querySelectorAll('.product-card__media');
    cards.forEach(function(card) {
        var img = new Image();
        var bgUrl = card.style.backgroundImage;
        if (bgUrl && bgUrl !== 'none') {
            card.classList.add('product-card__media--loading');
            img.onload = function() {
                card.classList.remove('product-card__media--loading');
            };
            img.onerror = function() {
                card.classList.remove('product-card__media--loading');
            };
            var url = bgUrl.replace(/url\(["']?/, '').replace(/["']?\)/, '');
            img.src = url;
        }
    });
})();
