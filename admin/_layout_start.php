<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? t('admin_dashboard');
$htmlLang = current_lang();
$htmlDir = is_rtl() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= esc($htmlLang) ?>" dir="<?= esc($htmlDir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){try{if(localStorage.getItem('medal-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();</script>
    <title><?= esc($pageTitle) ?> — <?= esc(t('admin_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(admin_asset('assets/css/admin.css?v=' . filemtime(__DIR__ . '/../assets/css/admin.css'))) ?>">
</head>
<body class="admin-body">

<header class="admin-mobile-header" aria-label="Mobile menu">
    <button type="button" class="admin-mobile-toggle" id="admin-nav-open" aria-expanded="false" aria-controls="admin-sidebar">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <p class="admin-mobile-brand">
        <a href="<?= esc(admin_url('index.php')) ?>">
            <img src="<?= esc(admin_asset('assets/img/logo.png')) ?>" alt="<?= esc(t('admin_brand')) ?>" style="height: 40px; width: auto; vertical-align: middle;">
        </a>
    </p>

</header>

<div class="admin-shell">
<aside class="admin-nav" id="admin-sidebar" aria-label="<?= esc(t('admin_nav_aria')) ?>">
    <div class="admin-nav-header">
        <p class="admin-brand">
            <a href="<?= esc(admin_url('index.php')) ?>">
                <img src="<?= esc(admin_asset('assets/img/logo.png')) ?>" alt="<?= esc(t('admin_brand')) ?>" style="width: 100%; max-width: 180px; height: auto;">
            </a>
        </p>
        <button type="button" class="admin-nav-close" id="admin-nav-close" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
<nav class="admin-sidebar-nav">
        <a href="<?= esc(admin_url('index.php')) ?>" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <span><?= esc(t('admin_nav_dashboard')) ?></span>
        </a>

        <?php if (admin_has_permission('orders')): ?>
        <div class="admin-nav-section">
            <button class="admin-nav-section__toggle" onclick="toggleNavSection(this)" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span>الطلبات</span>
                <svg class="admin-nav-section__arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="admin-nav-section__body" style="display:none">
                <a href="<?= esc(admin_url('orders.php')) ?>">كل الطلبات</a>
                <a href="<?= esc(admin_url('order_management.php')) ?>">طلب جديد</a>
                <a href="<?= esc(admin_url('broadcast.php')) ?>" style="color: #10b981; font-weight: 700;">📢 بث إعلانات الواتساب</a>
                <a href="<?= esc(admin_url('whatsapp_bot.php')) ?>" style="color: #25d366; font-weight: 700;">💬 بوت الواتساب والـ QR</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (admin_has_permission('products')): ?>
        <div class="admin-nav-section">
            <button class="admin-nav-section__toggle" onclick="toggleNavSection(this)" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 5 4 4"></path><path d="M13 3.832a1.988 1.988 0 0 0-2 0l-7 4.2a2 2 0 0 0-1 1.732V19a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9.764a2 2 0 0 0-1-1.732l-7-4.2Z"></path></svg>
                <span>المنتجات والعطور</span>
                <svg class="admin-nav-section__arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="admin-nav-section__body" style="display:none">
                <a href="<?= esc(admin_url('products.php')) ?>">العطور العادية</a>
                <?php if (admin_has_permission('offers')): ?>
                <a href="<?= esc(admin_url('offers.php')) ?>">العروض والتخفيضات</a>
                <?php endif; ?>
                <?php if (admin_has_permission('brands')): ?>
                <a href="<?= esc(admin_url('brand_products.php')) ?>">الماركات العالمية</a>
                <?php endif; ?>
                <?php if (admin_has_permission('internal_products')): ?>
                <a href="<?= esc(admin_url('internal_products.php')) ?>">الهدايا والمنتجات الداخلية</a>
                <?php endif; ?>
                <?php if (admin_has_permission('reviews')): ?>
                <a href="<?= esc(admin_url('reviews.php')) ?>">تقييمات المنتجات</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (admin_has_permission('categories')): ?>
        <a href="<?= esc(admin_url('categories.php')) ?>" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 3.6v16.8a.6.6 0 0 1-.6.6H3.6a.6.6 0 0 1-.6-.6V3.6a.6.6 0 0 1 .6-.6h16.8a.6.6 0 0 1 .6.6Z"></path><path d="M3 9h18"></path><path d="M3 15h18"></path></svg>
            <span><?= esc(t('admin_nav_categories')) ?></span>
        </a>
        <?php endif; ?>

        <?php if (admin_has_permission('promo_codes')): ?>
        <a href="<?= esc(admin_url('promo_codes.php')) ?>" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="7.5" r="2.5"></circle><circle cx="16.5" cy="16.5" r="2.5"></circle><line x1="21" y1="3" x2="3" y2="21"></line></svg>
            <span><?= esc(t('admin_nav_promo_codes')) ?></span>
        </a>
        <?php endif; ?>

        <?php if (admin_has_permission('clients')): ?>
        <a href="<?= esc(admin_url('clients.php')) ?>" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            <span>العملاء</span>
        </a>
        <?php endif; ?>

        <?php if (admin_has_permission('messages')): ?>
        <a href="<?= esc(admin_url('messages.php')) ?>" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h9"></path><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
            <span><?= esc(t('admin_nav_messages')) ?></span>
        </a>
        <?php endif; ?>

        <?php if (admin_has_permission('settings')): ?>
        <a href="<?= esc(admin_url('developer_hub.php')) ?>" class="admin-nav-item" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(99, 102, 241, 0.15)); border: 1px solid rgba(212, 175, 55, 0.4); color: #d4af37; font-weight: 800;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            <span>⚡ تحكم المطورين والصيانة</span>
        </a>
        <a href="<?= esc(admin_url('database_manage.php')) ?>" class="admin-nav-item" style="background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.25); color: #d4af37; font-weight: 700;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            <span>🗄️ قاعدة البيانات والاستيراد</span>
        </a>
        <a href="<?= esc(admin_url('whatsapp_bot.php')) ?>" class="admin-nav-item" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); color: #10b981; font-weight: 700;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span>🤖 بوت الواتساب والتأكيد</span>
        </a>
        <?php endif; ?>

        <?php if (admin_has_permission('settings')): ?>
        <div class="admin-nav-section">
            <button class="admin-nav-section__toggle" onclick="toggleNavSection(this)" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path></svg>
                <span>الإعدادات والتقارير</span>
                <svg class="admin-nav-section__arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="admin-nav-section__body" style="display:none">
                <a href="<?= esc(admin_url('settings.php')) ?>">إعدادات الموقع الأساسية</a>
                <a href="<?= esc(admin_url('database_manage.php')) ?>" style="color:#d4af37; font-weight:bold;">🗄️ قاعدة البيانات والنسخ الاحتياطي</a>
                <?php if (admin_has_permission('shipping')): ?>
                <a href="<?= esc(admin_url('shipping.php')) ?>">الشحن والتوصيل</a>
                <?php endif; ?>
                <?php if (admin_has_permission('faqs')): ?>
                <a href="<?= esc(admin_url('faqs.php')) ?>">الأسئلة الشائعة</a>
                <?php endif; ?>
                <?php if (admin_has_permission('about_settings')): ?>
                <a href="<?= esc(admin_url('about_settings.php')) ?>">صفحة من نحن</a>
                <?php endif; ?>
                <?php if (admin_has_permission('policy_settings')): ?>
                <a href="<?= esc(admin_url('policy_settings.php')) ?>">سياسة الإرجاع</a>
                <?php endif; ?>
                <?php if (admin_has_permission('reports')): ?>
                <a href="<?= esc(admin_url('reports.php')) ?>">التقارير</a>
                <?php endif; ?>
                <?php if (admin_has_permission('sales_records')): ?>
                <a href="<?= esc(admin_url('sales_records.php')) ?>">سجلات المبيعات</a>
                <?php endif; ?>
                <?php if (admin_has_permission('product_statistics')): ?>
                <a href="<?= esc(admin_url('product_statistics.php')) ?>">إحصائيات المنتجات</a>
                <?php endif; ?>
                <?php if (admin_has_permission('admins')): ?>
                <a href="<?= esc(admin_url('admins.php')) ?>">الموظفين</a>
                <a href="<?= esc(admin_url('roles.php')) ?>">الأدوار والصلاحيات</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <a href="<?= esc(storefront_url('index.php')) ?>" target="_blank" rel="noopener" class="admin-nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            <span><?= esc(t('admin_nav_view_site')) ?></span>
        </a>
        <a href="<?= esc(admin_url('logout.php')) ?>" class="admin-nav-item admin-nav-item--logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span><?= esc(t('admin_nav_logout')) ?></span>
        </a>
    </nav>
    <p class="admin-lang-switch" role="group" aria-label="<?= esc(t('lang_switch')) ?>">
        <a href="<?= esc(lang_switch_url('en')) ?>"<?= current_lang() === 'en' ? ' class="is-current"' : '' ?>><?= esc(t('lang_en')) ?></a>
        <span class="admin-lang-sep" aria-hidden="true">·</span>
        <a href="<?= esc(lang_switch_url('ar')) ?>"<?= current_lang() === 'ar' ? ' class="is-current"' : '' ?>><?= esc(t('lang_ar')) ?></a>
    </p>
    <button type="button" class="admin-theme-toggle" id="admin-theme-toggle"
        data-to-daylight="<?= esc(t('theme_to_daylight')) ?>"
        data-to-dark="<?= esc(t('theme_to_dark')) ?>"
        aria-label="<?= esc(t('theme_to_daylight')) ?>">
        <span class="admin-theme-toggle__sun" aria-hidden="true">☀</span>
        <span class="admin-theme-toggle__moon" aria-hidden="true">☾</span>
    </button>
<style>
.admin-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 0.6rem 1.2rem; color: var(--admin-sidebar-text);
    text-decoration: none; font-size: 0.9rem; border-radius: 8px;
    margin: 2px 8px; transition: all 0.2s;
}
.admin-nav-item:hover, .admin-nav-item.active {
    background: var(--admin-sidebar-hover); color: var(--admin-sidebar-active);
}
.admin-nav-item--logout { color: #e74c3c; }
.admin-nav-section { margin: 4px 0; }
.admin-nav-section__toggle {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 0.6rem 1.2rem; background: none; border: none;
    color: var(--admin-sidebar-text); font-size: 0.9rem; cursor: pointer;
    border-radius: 8px; margin: 0 8px; transition: all 0.2s;
    font-family: inherit; text-align: start;
}
.admin-nav-section__toggle:hover { background: var(--admin-sidebar-hover); color: #fff; }
.admin-nav-section__arrow {
    margin-inline-start: auto; transition: transform 0.2s;
    flex-shrink: 0;
}
.admin-nav-section__body {
    padding: 4px 0 4px 1.5rem;
}
.admin-nav-section__body a {
    display: flex; align-items: center; gap: 8px;
    padding: 0.45rem 1rem; color: var(--admin-sidebar-text);
    text-decoration: none; font-size: 0.82rem; border-radius: 6px;
    margin: 2px 8px; transition: all 0.2s;
}
.admin-nav-section__body a:hover, .admin-nav-section__body a.active {
    background: var(--admin-sidebar-hover); color: var(--admin-sidebar-active);
}
.admin-nav-section__body a::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%;
    background: var(--admin-sidebar-text); flex-shrink: 0; opacity: 0.5;
}
</style>
</aside>

<div class="admin-nav-backdrop" id="admin-nav-backdrop"></div>

<script>
    (function() {
        const btnOpen = document.getElementById('admin-nav-open');
        const btnClose = document.getElementById('admin-nav-close');
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('admin-nav-backdrop');
        const body = document.body;
        
        function openNav() {
            sidebar.classList.add('is-open');
            body.classList.add('nav-open');
            btnOpen.setAttribute('aria-expanded', 'true');
        }
        
        function closeNav() {
            sidebar.classList.remove('is-open');
            body.classList.remove('nav-open');
            btnOpen.setAttribute('aria-expanded', 'false');
        }

        if (btnOpen && btnClose && sidebar && backdrop) {
            btnOpen.addEventListener('click', openNav);
            btnClose.addEventListener('click', closeNav);
            backdrop.addEventListener('click', closeNav);
        }
    })();
function toggleNavSection(btn) {
    var body = btn.nextElementSibling;
    var arrow = btn.querySelector('.admin-nav-section__arrow');
    var isOpen = body.style.display !== 'none';
    
    document.querySelectorAll('.admin-nav-section__body').forEach(function(b) {
        if (b !== body) {
            b.style.display = 'none';
            var otherBtn = b.previousElementSibling;
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            var otherArrow = otherBtn ? otherBtn.querySelector('.admin-nav-section__arrow') : null;
            if (otherArrow) otherArrow.style.transform = 'rotate(-90deg)';
        }
    });
    
    if (isOpen) {
        body.style.display = 'none';
        btn.setAttribute('aria-expanded', 'false');
        if (arrow) arrow.style.transform = 'rotate(-90deg)';
    } else {
        body.style.display = 'block';
        btn.setAttribute('aria-expanded', 'true');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    }
}
(function() {
    var currentPage = window.location.pathname.split('/').pop().split('?')[0];
    document.querySelectorAll('.admin-nav-item, .admin-nav-section__body a').forEach(function(link) {
        var href = link.getAttribute('href');
        if (!href) return;
        var linkPage = href.split('/').pop().split('?')[0];
        if (linkPage === currentPage) {
            link.classList.add('active');
            var section = link.closest('.admin-nav-section');
            if (section) {
                var body = section.querySelector('.admin-nav-section__body');
                var toggle = section.querySelector('.admin-nav-section__toggle');
                if (body && body.style.display === 'none') {
                    body.style.display = 'block';
                    if (toggle) toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
})();
</script>

<main class="admin-main">
