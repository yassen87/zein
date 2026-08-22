<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_bootstrap.php';

$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$required = null;

$map = [
    'orders.php' => 'orders',
    'order_view.php' => 'orders',
    'order_management.php' => 'order_management',
    'orders_export.php' => 'orders_export',
    'fintech_transfers.php' => 'orders',
    'ajax_fintech_handler.php' => 'orders',
    'products.php' => 'products',
    'product_edit.php' => 'products',
    'product_save.php' => 'products',
    'offers.php' => 'offers',
    'offer_edit.php' => 'offers',
    'offer_bundle_save.php' => 'offers',
    'offer_bundle_delete.php' => 'offers',
    'offer_product_edit.php' => 'offers',
    'offers_save.php' => 'offers',
    'brands.php' => 'brands',
    'brand_edit.php' => 'brands',
    'brand_products.php' => 'brands',
    'brand_save.php' => 'brands',
    'brand_delete.php' => 'brands',
    'internal_products.php' => 'internal_products',
    'categories.php' => 'categories',
    'category_save.php' => 'categories',
    'promo_codes.php' => 'promo_codes',
    'promo_code_save.php' => 'promo_codes',
    'clients.php' => 'clients',
    'client_edit.php' => 'clients',
    'client_save.php' => 'clients',
    'client_delete.php' => 'clients',
    'customers.php' => 'customers',
    'client_statement.php' => 'client_statement',
    'clients_export.php' => 'clients_export',
    'messages.php' => 'messages',
    'notifications.php' => 'notifications',
    'settings.php' => 'settings',
    'admins.php' => 'admins',
    'roles.php' => 'admins',
    'shipping.php' => 'shipping',
    'faqs.php' => 'faqs',
    'about_settings.php' => 'about_settings',
    'policy_settings.php' => 'policy_settings',
    'reviews.php' => 'reviews',
    'review_save.php' => 'reviews',
    'review_delete.php' => 'reviews',
    'reports.php' => 'reports',
    'sales_records.php' => 'sales_records',
    'product_statistics.php' => 'product_statistics',
];

$required = $map[$script] ?? null;

require_admin($required);
