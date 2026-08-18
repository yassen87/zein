<?php

declare(strict_types=1);

function nav_sections(): array
{
    return [
        'main' => [
            'label' => 'الرئيسية',
            'dir' => 'main',
            'items' => [
                ['route' => 'dashboard', 'label' => 'لوحة التحكم'],
                ['route' => 'pos', 'label' => 'الكاشير POS'],
                ['route' => 'invoices', 'label' => 'الفواتير'],
                ['route' => 'invoice_view', 'label' => 'تفاصيل الفاتورة', 'hidden' => true],
            ],
        ],
        'catalog' => [
            'label' => 'المنتجات والمخزون',
            'dir' => 'catalog',
            'items' => [
                ['route' => 'products', 'label' => 'المنتجات'],
                ['route' => 'product_create', 'label' => 'إضافة منتج', 'hidden' => true],
                ['route' => 'product_edit', 'label' => 'تعديل منتج', 'hidden' => true],
                ['route' => 'recipes', 'label' => 'التركيبات'],
                ['route' => 'formula_defaults', 'label' => 'الجرامات الافتراضية'],
                ['route' => 'inventory', 'label' => 'المخزن'],
                ['route' => 'inventory_add', 'label' => 'إضافة مخزون مركزي', 'hidden' => true],
                ['route' => 'branch_inventory', 'label' => 'رصيد الفرع'],
                ['route' => 'transfers_supply', 'label' => 'توريد (مخزن → فرع)'],
                ['route' => 'transfers_supply_create', 'label' => 'إنشاء توريد جديد', 'hidden' => true],
                ['route' => 'transfers_branch', 'label' => 'تحويل بين الفروع'],
                ['route' => 'transfers_branch_create', 'label' => 'إنشاء تحويل جديد', 'hidden' => true],
                ['route' => 'transfers', 'label' => 'تحويلات المخزون القديم', 'hidden' => true],
                ['route' => 'returns', 'label' => 'المرتجعات'],
                ['route' => 'waste', 'label' => 'الهالك والتالف'],
                ['route' => 'print_barcode', 'label' => 'طباعة الباركود', 'hidden' => true],
            ],
        ],
        'customers' => [
            'label' => 'إدارة العملاء',
            'dir' => 'people',
            'items' => [
                ['route' => 'customers', 'label' => 'العملاء والديون'],
                ['route' => 'customer_view', 'label' => 'ملف العميل', 'hidden' => true],
                ['route' => 'quick_add_customer', 'label' => 'إضافة عميل سريع', 'hidden' => true],
            ],
        ],
        'hr' => [
            'label' => 'الموارد البشرية',
            'dir' => 'people',
            'items' => [
                ['route' => 'users', 'label' => 'الموظفون'],
                ['route' => 'attendance', 'label' => 'الحضور والغياب'],
                ['route' => 'payroll', 'label' => 'الرواتب والعمولات'],
                ['route' => 'shifts', 'label' => 'تسويات الشيفتات'],
                ['route' => 'targets', 'label' => 'التارجت اليومي'],
            ],
        ],
        'finance' => [
            'label' => 'المالية والتقارير',
            'dir' => 'finance',
            'items' => [
                ['route' => 'expenses', 'label' => 'المصاريف'],
                ['route' => 'suppliers', 'label' => 'الموردين'],
                ['route' => 'reports', 'label' => 'التقارير'],
            ],
        ],
        'online' => [
            'label' => 'الأونلاين والموقع',
            'dir' => 'online',
            'items' => [
                ['route' => 'website', 'label' => 'صفحات الموقع'],
                ['route' => 'online_orders', 'label' => 'طلبات الأونلاين'],
            ],
        ],
        'system' => [
            'label' => 'النظام والمتابعة',
            'dir' => 'system',
            'items' => [
                ['route' => 'notifications', 'label' => 'التنبيهات'],
                ['route' => 'locations', 'label' => 'الفروع والمواقع'],
                ['route' => 'audit', 'label' => 'سجل الأنشطة والعمليات'],
                ['route' => 'backup', 'label' => 'النسخ الاحتياطي'],
                ['route' => 'settings', 'label' => 'الإعدادات'],
            ],
        ],
    ];
}

function section_for_route(string $route): string
{
    foreach (nav_sections() as $key => $section) {
        foreach ($section['items'] as $item) {
            if ($item['route'] === $route) {
                return $key;
            }
        }
    }
    return 'main';
}

function page_path_for_route(string $route): ?string
{
    foreach (nav_sections() as $section) {
        foreach ($section['items'] as $item) {
            if ($item['route'] === $route) {
                return __DIR__ . '/pages/' . $section['dir'] . '/' . $route . '.php';
            }
        }
    }
    return null;
}

function all_routes(): array
{
    $routes = [];
    foreach (nav_sections() as $section) {
        foreach ($section['items'] as $item) {
            $routes[] = $item['route'];
        }
    }
    return $routes;
}
