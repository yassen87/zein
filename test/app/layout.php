<?php

declare(strict_types=1);

require_once __DIR__ . '/navigation.php';

function lang_switch_url(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    return 'index.php?' . http_build_query($params);
}

function render_login(): void
{
    $flash = flash();
    $lang = current_lang();
    $dir = $lang === 'en' ? 'ltr' : 'rtl';
    ?>
    <!doctype html>
    <html lang="<?= $lang ?>" dir="<?= $dir ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e(__('تسجيل الدخول')) ?></title>
        <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
        <script>
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        </script>
    </head>
    <body class="login-page">
    <main class="login-card">
        <div class="brand-mark"><img src="<?= e(APP_BASE) ?>/assets/logo.png" alt="logo" /></div>
        <h1><?= e(APP_NAME) ?></h1>
        <p><?= e(__('نظام إدارة براند العطور')) ?></p>
        
        <div style="margin-bottom: 15px; display: flex; gap: 8px; justify-content: center; font-size: 12px; align-items: center;">
            <a href="?lang=ar" style="font-weight: <?= $lang === 'ar' ? 'bold' : 'normal' ?>; text-decoration: none;">العربية</a>
            <span>|</span>
            <a href="?lang=en" style="font-weight: <?= $lang === 'en' ? 'bold' : 'normal' ?>; text-decoration: none;">English</a>
            <span>|</span>
            <button onclick="toggleTheme()" style="background: none; border: none; cursor: pointer; padding: 0; font-size: 14px;" title="Theme">🌓</button>
        </div>

        <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e(__($flash['message'])) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label><?= e(__('اسم المستخدم')) ?><input name="username" required autofocus></label>
            <label><?= e(__('كلمة المرور')) ?><input name="password" type="password" required></label>
            <button class="btn primary"><?= e(__('دخول')) ?></button>
        </form>
    </main>
    <script>
        function toggleTheme() {
            const root = document.documentElement;
            const current = root.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        }
    </script>
    </body>
    </html>
    <?php
}

function render_layout(string $route, array $user): void
{
    $flash = flash();
    $activeSection = section_for_route($route);
    $routeLabel = label_for_route($route);
    $sectionLabel = nav_sections()[$activeSection]['label'] ?? 'الرئيسية';
    $lang = current_lang();
    $dir = $lang === 'en' ? 'ltr' : 'rtl';
    ?>
    <!doctype html>
    <html lang="<?= $lang ?>" dir="<?= $dir ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e(APP_NAME) ?></title>
        <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
        <script>
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        </script>
        <script src="assets/app.js?v=<?= filemtime(__DIR__ . '/../assets/app.js') ?>" defer></script>
    </head>
    <body data-active-section="<?= e($activeSection) ?>">
    <button class="mobile-menu-button" type="button" data-menu-toggle aria-label="<?= e(__('القائمة')) ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
        <span><?= e(__('القائمة')) ?></span>
    </button>
    <div class="page-backdrop" data-menu-backdrop></div>
    <aside class="sidebar">
        <button class="sidebar-close-button" type="button" data-menu-close aria-label="Close">×</button>
        <div class="side-brand">
            <div class="brand-mark"><img src="<?= e(APP_BASE) ?>/assets/logo.png" alt="logo" /></div>
            <div>
                <h1><?= e(APP_NAME) ?></h1>
                <small><?= e(__('نظام إدارة براند العطور')) ?></small>
            </div>
        </div>

        <div class="side-search">
            <input type="search" placeholder="<?= e(__('بحث سريع داخل القائمة')) ?>" data-nav-search>
        </div>

        <nav class="nav-accordion" aria-label="Main Navigation">
            <?php foreach (nav_sections() as $key => $section): 
                $visibleItems = array_filter($section['items'], fn($item) => empty($item['hidden']) && has_permission($item['route']));
                if (empty($visibleItems)) { continue; }
            ?>
                <section class="nav-section <?= $key === $activeSection ? 'open' : '' ?>" data-section="<?= e($key) ?>">
                    <button type="button" class="nav-section-toggle" aria-expanded="<?= $key === $activeSection ? 'true' : 'false' ?>">
                        <span><?= e(__($section['label'])) ?></span>
                        <b>⌄</b>
                    </button>
                    <div class="nav-section-panel">
                        <?php foreach ($visibleItems as $item): ?>
                            <?= nav_link($item['route'], $item['label'], $route) ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </nav>

        <div class="user-box">
            <strong><?= e($user['name']) ?></strong>
            <span><?= e(__($user['role_name'])) ?><?= !empty($user['location_name']) ? ' - ' . e(__($user['location_name'])) : '' ?></span>
            <a href="index.php?r=logout"><?= e(__('خروج')) ?></a>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <div>
                <div class="breadcrumbs">
                    <span><?= e(__($sectionLabel)) ?></span>
                    <b>/</b>
                    <strong><?= e(__($routeLabel)) ?></strong>
                </div>
                <h2><?= e(__($routeLabel)) ?></h2>
            </div>
            <div class="topbar-actions" style="display: flex; gap: 8px; align-items: center;">
                <a class="quick-action" href="index.php?r=pos"><?= e(__('بيع جديد')) ?></a>
                <a class="quick-action" href="index.php?r=notifications"><?= e(__('التنبيهات')) ?></a>
                <a class="quick-action primary" href="index.php?r=reports"><?= e(__('التقارير')) ?></a>
                
                <!-- Theme Toggle Button -->
                <button class="quick-action" onclick="toggleTheme()" type="button" title="🌓 Toggle Theme" style="border: 0; cursor: pointer; font-size: 14px; padding: 6px 10px; display: inline-flex; align-items: center; justify-content: center; height: 32px; border-radius: 8px; background: rgba(0,0,0,0.05);">🌓</button>
                
                <!-- Language Switcher Button -->
                <?php if ($lang === 'en'): ?>
                    <a class="quick-action" href="<?= e(lang_switch_url('ar')) ?>" style="font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 32px; padding: 6px 10px; border-radius: 8px;">العربية</a>
                <?php else: ?>
                    <a class="quick-action" href="<?= e(lang_switch_url('en')) ?>" style="font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 32px; padding: 6px 10px; border-radius: 8px;">English</a>
                <?php endif; ?>
            </div>
        </header>
        <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e(__($flash['message'])) ?></div><?php endif; ?>
        <section class="page-shell">
            <?php render_page($route, $user); ?>
        </section>
    </main>
    <script>
        function toggleTheme() {
            const root = document.documentElement;
            const current = root.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        }
    </script>
    </body>
    </html>
    <?php
}

function nav_link(string $target, string $label, string $route): string
{
    return '<a class="' . ($target === $route ? 'active' : '') . '" href="index.php?r=' . e($target) . '" data-nav-item><span>' . e(__($label)) . '</span></a>';
}

function label_for_route(string $route): string
{
    foreach (nav_sections() as $section) {
        foreach ($section['items'] as $item) {
            if ($item['route'] === $route) {
                return __($item['label']);
            }
        }
    }
    return __('لوحة التحكم');
}

function simple_table(array $rows): string
{
    if (!$rows) {
        return '<p class="muted">' . e(__('لا توجد بيانات بعد.')) . '</p>';
    }

    $html = '<table><thead><tr>';
    foreach (array_keys($rows[0]) as $key) {
        $html .= '<th>' . e(__($key)) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . e($value) . '</td>';
        }
        $html .= '</tr>';
    }

    return $html . '</tbody></table>';
}

function table_invoices(array $invoices): void
{
    ?>
    <div class="panel">
        <table>
            <thead>
            <tr>
                <th><?= e(__('رقم الفاتورة')) ?></th>
                <th><?= e(__('الحالة')) ?></th>
                <th><?= e(__('الموقع')) ?></th>
                <th><?= e(__('الموظف')) ?></th>
                <th><?= e(__('العميل')) ?></th>
                <th><?= e(__('الإجمالي')) ?></th>
                <th><?= e(__('المدفوع')) ?></th>
                <th><?= e(__('المتبقي')) ?></th>
                <th><?= e(__('ملاحظات')) ?></th>
                <th><?= e(__('التاريخ')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $i): ?>
                <tr>
                    <td><a href="index.php?r=invoice_view&id=<?= e($i['id']) ?>"><?= e($i['invoice_number']) ?></a></td>
                    <td><span class="badge"><?= e(__($i['status'])) ?></span></td>
                    <td><?= e($i['location_name']) ?></td>
                    <td><?= e($i['user_name']) ?></td>
                    <td><?= e($i['customer_name'] ?: __('زبون عابر')) ?></td>
                    <td><?= money($i['total']) ?></td>
                    <td><?= money($i['paid_total']) ?></td>
                    <td><?= money($i['due_total']) ?></td>
                    <td><?= e($i['notes']) ?></td>
                    <td><?= e($i['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
