<?php
declare(strict_types=1);
?>
    </main>
    <footer class="footer-pc">
        <div class="container footer-pc__container">
            <div class="footer-pc__grid">
                <!-- Column 1: Brand -->
                <div class="footer-pc__col footer-pc__col--brand">
                    <a href="<?= esc(url('index.php')) ?>" class="footer-pc__logo">
                        <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(site_name()) ?>" style="height:38px;width:auto;" loading="lazy" width="38" height="38">
                    </a>
                    <p class="footer-pc__tagline">
                        <?= current_lang() === 'ar' ? 'عطور حِرفية فاخرة مصممة لتترك أثراً يدوم طويلاً بذكرى جميلة.' : 'Luxury artisan perfumes crafted to leave a long-lasting memory.' ?>
                    </p>
                    <div class="footer-pc__socials">
                        <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" aria-label="WhatsApp">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.733-1.464L0 24zm6.59-4.846c1.6.95 3.198 1.451 4.937 1.452 5.43 0 9.85-4.417 9.85-9.848 0-2.63-1.025-5.101-2.887-6.963C16.643 1.98 14.17 .957 11.54 0.957c-5.434 0-9.852 4.417-9.855 9.85-.001 1.838.5 3.633 1.451 5.23L1.932 21.96l5.715-1.5c-1.6.95-3.198 1.451-4.937 1.452h.001z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                        <a href="#" aria-label="Facebook">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                    </div>
                </div>



                <!-- Column 3: Customer Care -->
                <div class="footer-pc__col">
                    <h4 class="footer-pc__title"><?= current_lang() === 'ar' ? 'روابط تهمك' : 'Help & Info' ?></h4>
                    <ul class="footer-pc__links">
                        <li><a href="<?= esc(url('track_order.php')) ?>"><?= current_lang() === 'ar' ? 'تتبع طلبك' : 'Track Your Order' ?></a></li>
                        <li><a href="<?= esc(url('about.php')) ?>"><?= current_lang() === 'ar' ? 'من نحن' : 'About Us' ?></a></li>
                        <li><a href="<?= esc(url('index.php#faq')) ?>"><?= current_lang() === 'ar' ? 'الأسئلة الشائعة' : 'FAQs' ?></a></li>
                        <li><a href="<?= esc(url('policy.php')) ?>"><?= current_lang() === 'ar' ? 'سياسة الإرجاع والتبديل' : 'Exchange Policy' ?></a></li>
                        <li><a href="<?= esc(url('privacy.php')) ?>"><?= current_lang() === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy' ?></a></li>
                        <li><a href="<?= esc(url('terms.php')) ?>"><?= current_lang() === 'ar' ? 'شروط الخدمة' : 'Terms of Service' ?></a></li>
                        <li><a href="<?= esc(url('contact.php')) ?>"><?= current_lang() === 'ar' ? 'تواصل معنا' : 'Contact Us' ?></a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact -->
                <div class="footer-pc__col">
                    <h4 class="footer-pc__title"><?= current_lang() === 'ar' ? 'تواصل معنا' : 'Contact Us' ?></h4>
                    <ul class="footer-pc__contact-list">
                        <li>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="<?= esc(contact_phone_href()) ?>" style="direction: ltr;"><?= esc(CONTACT_PHONE_TEL) ?></a>
                        </li>
                        <li>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>support@zainperfumes.com</span>
                        </li>
                        <li>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span><?= esc(t('contact_hours_val')) ?></span>
                        </li>
                        <li>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?= current_lang() === 'ar' ? '18 شارع منشية البكري، القاهرة' : '18 Mansheya El-Bakry St., Cairo' ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-pc__bottom">
                <p>&copy; <?= date('Y') ?> <?= esc(site_name()) ?>. <?= current_lang() === 'ar' ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' ?></p>
            </div>
        </div>
    </footer>

    

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav">
        <a href="<?= esc(url('index.php')) ?>" class="bottom-nav-item <?= ($pageTitle === t('page_home') || !isset($pageTitle)) ? 'is-active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span><?= esc(t('bottom_nav_home')) ?></span>
        </a>
        <a href="<?= esc(url('categories.php')) ?>" class="bottom-nav-item <?= ($pageTitle === t('bottom_nav_categories')) ? 'is-active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <span><?= esc(t('bottom_nav_categories')) ?></span>
        </a>
        <a href="<?= esc(url('search.php')) ?>" class="bottom-nav-item <?= ($pageTitle === t('search_placeholder')) ? 'is-active' : '' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
            <span><?= esc(t('search_button')) ?></span>
        </a>
        <a href="<?= esc(url('wishlist.php')) ?>" class="bottom-nav-item <?= ($pageTitle === t('bottom_nav_wishlist')) ? 'is-active' : '' ?>" style="position: relative;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <span class="wishlist-badge" style="display: none; top: 5px; left: 50%; margin-left: 10px;">0</span>
            <span><?= esc(t('bottom_nav_wishlist')) ?></span>
        </a>
        <?php
        $accountUrl = url('client/login.php');
        if (is_client_logged_in()) {
            $accountUrl = url('client/dashboard.php');
        }
        ?>
        <a href="<?= esc($accountUrl) ?>" class="bottom-nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span><?= esc(t('bottom_nav_account')) ?></span>
        </a>
    </nav>

    <!-- Quick Add Modal (Redesigned) -->
    <dialog class="modal-configure" id="quick-add-modal">
        <div class="modal-configure__content">
            <div class="modal-pull-handle desktop-hide"></div>
            <button class="modal-configure__close" aria-label="Close modal">&times;</button>
            <div class="modal-configure__body">
                <div class="modal-configure__image-col">
                    <div class="product-visual-container">
                        <img id="modal-image" src="" alt="" style="display: none;" loading="lazy">
                        <div id="modal-image-placeholder" style="font-size: 3rem; color: rgba(0,0,0,0.1);">✦</div>
                    </div>
                </div>
                <div class="modal-configure__details-col">
                    <h2 id="modal-title" class="modal-title"></h2>
                    <div class="modal-price" id="modal-price-display"></div>
                    
                    <div class="modal-section">
                        <p class="modal-section-label"><?= esc(t('product_label_variant')) ?>: <span id="selected-variant-label" style="font-weight:700;"></span></p>
                        <div class="variant-grid-modern" id="modal-variants">
                            <!-- JS Populated -->
                        </div>
                    </div>

                    <form class="modal-configure__form" id="modal-form" method="post" action="<?= esc(url('cart.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" id="modal-product-id">
                        
                        <div class="modal-qty-wrapper">
                            <div class="modal-qty-controls">
                                <button type="button" class="qty-btn" data-action="minus">&minus;</button>
                                <input type="number" name="quantity" value="1" min="1" max="99" id="modal-qty">
                                <button type="button" class="qty-btn" data-action="plus">&plus;</button>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="submit" name="add" class="modal-btn modal-btn--add modal-add-btn">
                                <?= esc(t('product_btn_add')) ?>
                            </button>
                            <button type="submit" name="checkout" class="modal-btn modal-btn--buy modal-checkout-btn">
                                <?= esc(t('cart_checkout')) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Cart Side Drawer -->
    <div class="cart-drawer-overlay" id="cart-drawer-overlay" onclick="closeCartDrawer()"></div>
    <div class="cart-drawer" id="cart-drawer">
        <div class="cart-drawer__header">
            <div style="display:flex;align-items:center;gap:0.6rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <h3 style="margin:0;font-size:1.1rem;font-weight:800;font-family:'Tajawal',sans-serif;"><?= current_lang() === 'ar' ? 'سلة التسوق' : 'Shopping Cart' ?></h3>
            </div>
            <button class="cart-drawer__close" onclick="closeCartDrawer()" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="cart-drawer__body" id="cart-drawer-items">
            <!-- JS Populated -->
        </div>
        <div class="cart-drawer__footer">
            <div class="cart-drawer__subtotal">
                <span style="color:#888;font-size:0.9rem;"><?= current_lang() === 'ar' ? 'المجموع الفرعي' : 'Subtotal' ?></span>
                <span id="cart-drawer-subtotal-val" style="font-size:1.15rem;font-weight:800;color:#111;">0.00 ج.م</span>
            </div>
            <a href="<?= esc(url('checkout.php')) ?>" class="cart-drawer__btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-inline-end:0.4rem;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                <?= current_lang() === 'ar' ? 'اشتري الآن' : 'Checkout' ?>
            </a>
            <a href="<?= esc(url('checkout.php')) ?>" style="display:block;text-align:center;color:#999;font-size:0.82rem;margin-top:0.6rem;text-decoration:underline;font-family:'Tajawal',sans-serif;">
                <?= current_lang() === 'ar' ? 'أو استمر في التسوق' : 'or continue shopping' ?>
            </a>
        </div>
    </div>

    <script src="<?= url('assets/js/cart-drawer.js?v=' . filemtime(__DIR__ . '/../assets/js/cart-drawer.js')) ?>" defer></script>
    
    <!-- WhatsApp Floating Button -->
    <a href="<?= esc(contact_whatsapp_url()) ?>" target="_blank" rel="noopener" class="wa-float" aria-label="<?= esc(t('wa_float_aria')) ?>">
        <span class="wa-float__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </span>
    </a>

    <script src="<?= esc(url('assets/js/main.js?v=' . filemtime(__DIR__ . '/../assets/js/main.js'))) ?>" defer></script>

    <button id="scroll-top" class="scroll-top" aria-label="<?= t('scroll_to_top') ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>

    <?php 
    $gaId = trim(get_setting('ga_id', ''));
    if (!empty($gaId) && $gaId !== 'ga_id' && $gaId !== 'null' && preg_match('/^(G-[A-Z0-9]+|UA-[0-9-]+)$/i', $gaId)): 
    ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($gaId) ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= esc($gaId) ?>');
    </script>
    <?php endif; ?>

    <?php 
    $fbPixelId = trim(get_setting('fb_pixel_id', ''));
    if (!empty($fbPixelId) && $fbPixelId !== 'fb_pixel_id' && $fbPixelId !== 'null' && preg_match('/^\d{6,25}$/', $fbPixelId)): 
    ?>
    <!-- Facebook Pixel -->
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= esc($fbPixelId) ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= esc($fbPixelId) ?>&ev=PageView&noscript=1"/></noscript>
    <?php endif; ?>
</body>
</html>
