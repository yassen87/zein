<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // For production: ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
cart_normalize_session();

// CSRF Protection
get_csrf_token();

require_once __DIR__ . '/locale.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';
locale_bootstrap();

define('SITE_NAME', t('site_name'));

/** E.164 without + — edit for your WhatsApp Business number */
const CONTACT_WHATSAPP_E164 = '201111026600';
/** Display / tel: value */
const CONTACT_PHONE_TEL = '+20 11 11026600';

function contact_whatsapp_url(int $num = 1): string
{
    return 'https://wa.me/' . CONTACT_WHATSAPP_E164;
}

function contact_phone_href(): string
{
    return 'tel:' . str_replace(' ', '', CONTACT_PHONE_TEL);
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $phpSelf = $_SERVER['PHP_SELF'] ?? '';
    
    // Use SCRIPT_NAME as it's the most reliable for base path detection
    $currentPath = $scriptName !== '' ? $scriptName : $phpSelf;
    
    if ($currentPath === '') {
        return '';
    }

    $base = rtrim(dirname($currentPath), '/\\');
    
    // If the file is in a subdirectory like 'includes', 'admin', 'client', or 'api', we need to go up
    $dirName = basename($base);
    if ($dirName === 'includes' || $dirName === 'admin' || $dirName === 'api' || $dirName === 'client' || $dirName === 'deploy' || $dirName === 'deploy_host') {
        $base = rtrim(dirname($base), '/\\');
    }
    
    // Ensure we don't return just a dot or slash
    if ($base === '.' || $base === '/' || $base === '\\') {
        $base = '';
    }
    
    return $base;
}

function url(string $path): string
{
    $path = ltrim($path, '/');
    $base = base_path();
    
    $hash = '';
    if (strpos($path, '#') !== false) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    
    // For static assets, we don't need the full URL with domain often, 
    // but for consistent behavior we'll build it.
    $scheme = 'http';
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        $scheme = 'https';
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $fullBase = $scheme . '://' . $host . $base;
    
    $file = $path;
    $q = [];
    if (strpos($path, '?') !== false) {
        [$file, $qs] = explode('?', $path, 2);
        parse_str($qs, $q);
    }

    $fileLower = strtolower($file);
    $isStatic = (strpos($fileLower, 'assets/') === 0)
        || (bool) preg_match('/\.(css|js|mjs|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$/i', $file);

    if (!$isStatic) {
        $q['lang'] = current_lang();
    }

    $url = $fullBase . '/' . $file;
    if ($q !== []) {
        $url .= '?' . http_build_query($q);
    }
    
    return $url . $hash;
}

function base_url(string $path = ''): string
{
    return url($path);
}

function get_category_emoji(string $slug): string
{
    $slug = strtolower($slug);
    if (strpos($slug, 'men') !== false) return '👔';
    if (strpos($slug, 'women') !== false) return '👗';
    if (strpos($slug, 'oud') !== false) return '🪵';
    if (strpos($slug, 'unisex') !== false) return '🧴';
    if (strpos($slug, 'khinat') !== false || strpos($slug, 'khanaat') !== false) return '✨';
    return '✨';
}

function get_home_category_emoji(string $slug): string
{
    return get_category_emoji($slug);
}

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if (!function_exists('is_client_logged_in')) {
    function is_client_logged_in(): bool
    {
        return !empty($_SESSION['client_id']);
    }
}

if (!function_exists('require_client')) {
    function require_client(): void
    {
        if (!is_client_logged_in()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        return !empty($_SESSION['medal_admin_id']);
    }
}

if (!function_exists('admin_is_logged_in')) {
    function admin_is_logged_in(): bool
    {
        return is_admin_logged_in();
    }
}

function product_image_style(?string $imageKey): string
{
    if (!$imageKey || $imageKey === 'default') {
        return '';
    }
    if (strpos($imageKey, 'http://') === 0 || strpos($imageKey, 'https://') === 0) {
        return ' style="background-image: url(\'' . esc($imageKey) . '\'); background-size: cover; background-position: center;"';
    }
    // If it starts with img_ or has a dot, it's an upload
    if (strpos($imageKey, 'img_') === 0 || strpos($imageKey, '.') !== false) {
        return ' style="background-image: url(\'' . esc(url('assets/uploads/' . $imageKey)) . '\'); background-size: cover; background-position: center;"';
    }
    // Static assets
    $path = url('assets/img/' . $imageKey . '.jpg');
    return ' style="background-image: url(\'' . esc($path) . '\'); background-size: cover; background-position: center;"';
}

function product_image_class(?string $imageKey, string $baseClass = 'product-visual'): string
{
    return $baseClass;
}

function cart_line_key(int $productId, ?int $variantId): string
{
    $v = $variantId ?? 0;
    return $productId . '-' . $v;
}

/** @return array{product_id:int, variant_id:?int} */
function cart_parse_line_key(string $key): array
{
    $parts = explode('-', $key, 2);
    $pid = (int) ($parts[0] ?? 0);
    $vid = isset($parts[1]) ? (int) $parts[1] : 0;
    return ['product_id' => $pid, 'variant_id' => $vid > 0 ? $vid : null];
}

function cart_normalize_session(): void
{
    $c = $_SESSION['cart'];
    if ($c === []) {
        return;
    }
    $firstKey = (string) array_key_first($c);
    if (strpos($firstKey, '-') !== false) {
        return;
    }
    $new = [];
    foreach ($c as $pid => $qty) {
        if (!is_numeric($pid)) {
            continue;
        }
        $new[cart_line_key((int) $pid, null)] = (int) $qty;
    }
    $_SESSION['cart'] = $new;
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function add_to_cart(int $productId, int $qty = 1, ?int $variantId = null): void
{
    if ($qty < 1) {
        $qty = 1;
    }
    $k = cart_line_key($productId, $variantId);
    if (!isset($_SESSION['cart'][$k])) {
        $_SESSION['cart'][$k] = 0;
    }
    $_SESSION['cart'][$k] += $qty;
}

function remove_cart_line(string $lineKey): void
{
    unset($_SESSION['cart'][$lineKey]);
}

function remove_from_cart(int $productId): void
{
    foreach ($_SESSION['cart'] as $key => $_) {
        $parsed = cart_parse_line_key((string) $key);
        if ($parsed['product_id'] === $productId) {
            unset($_SESSION['cart'][$key]);
        }
    }
}

/**
 * Add a notification for admins.
 */
function add_admin_notification(string $type, string $titleAr, string $titleEn, ?string $messageAr = null, ?string $messageEn = null, ?string $link = null): void
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare('INSERT INTO admin_notifications (type, title_ar, title_en, message_ar, message_en, link) VALUES (?, ?, ?, ?, ?, ?)');
            $st->execute([$type, $titleAr, $titleEn, $messageAr, $messageEn, $link]);
        } catch (Throwable $e) {
            error_log('Error in config.php add_admin_notification: ' . $e->getMessage());
        }
    }
}

function format_price(float|int|string $amount): string
{
    return number_format((float)$amount, 2) . ' ' . t('currency');
}

/**
 * Fetch a localized setting from the database, falling back to the translation file.
 */
function get_setting(string $key, ?string $default = null): string
{
    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare('SELECT setting_value_en, setting_value_ar FROM settings WHERE setting_key = ?');
            $st->execute([$key]);
            $row = $st->fetch();
            if ($row) {
                $val = current_lang() === 'ar' ? $row['setting_value_ar'] : $row['setting_value_en'];
                if ($val !== null && trim((string)$val) !== '' && strtolower(trim((string)$val)) !== 'null') {
                    return trim((string)$val);
                }
            }
        } catch (Throwable $e) {
            error_log('Error in config.php get_setting: ' . $e->getMessage());
        }
    }
    if ($default !== null) {
        return $default;
    }
    $trans = t($key);
    return ($trans !== $key) ? $trans : '';
}

/**
 * Fetch all FAQs ordered by sort_order.
 */
function get_all_faqs(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = medal_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->query('SELECT question_en, question_ar, answer_en, answer_ar FROM faqs ORDER BY sort_order ASC, id ASC');
            $cache = $st->fetchAll();
            return $cache;
        } catch (Throwable $e) {
            error_log('Error in config.php get_all_faqs: ' . $e->getMessage());
        }
    }
    $cache = [];
    return $cache;
}

function generate_csrf_token(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        return generate_csrf_token();
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(get_csrf_token()) . '">';
}

function get_site_name() {
    return 'Zain Perfumes | زين للعطور';
}

function get_site_description() {
    $lang = current_lang();
    return $lang === 'ar' 
        ? 'متجر عطور فاخرة - أفخر أنواع العطور العربية والفرنسية بأفضل الأسعار. توصيل لجميع المحافظات.'
        : 'Luxury Perfume Store - Premium Arabic and French fragrances at the best prices. Delivery to all governorates.';
}

function get_page_description($page = 'home') {
    $lang = current_lang();
    $descriptions = [
        'home' => [
            'ar' => 'متجر زين للعطور - تسوق أجمل العطور الفاخرة والعربية والفرنسية. عروض حصرية، توصيل سريع، وأفضل الأسعار في مصر.',
            'en' => 'Zain Perfumes - Shop premium luxury fragrances, Arabic and French perfumes. Exclusive offers, fast delivery, and best prices in Egypt.'
        ],
        'products' => [
            'ar' => 'تصفح مجموعة كاملة من العطور الفاخرة. عطور عربية، فرنسية، عروض وباقات حصرية من زين للعطور.',
            'en' => 'Browse our complete collection of luxury perfumes. Arabic, French, exclusive offers and bundles from Zain Perfumes.'
        ],
        'offers' => [
            'ar' => 'أفضل العروض والتخفيضات على العطور الفاخرة. باقات حصرية وأسعار لا تقبل المنافسة من زين للعطور.',
            'en' => 'Best offers and discounts on luxury perfumes. Exclusive bundles and unbeatable prices from Zain Perfumes.'
        ],
        'about' => [
            'ar' => 'تعرف على زين للعطور - قصتنا، رؤيتنا، وشغفنا بتقديم أرقى العطور العربية والفرنسية.',
            'en' => 'About Zain Perfumes - Our story, vision, and passion for delivering the finest Arabic and French fragrances.'
        ],
        'contact' => [
            'ar' => 'اتصل بزين للعطور - خدمة عملاء متميزة، واتساب، تليفون، وعنوان المتجر.',
            'en' => 'Contact Zain Perfumes - Premium customer service, WhatsApp, phone, and store address.'
        ],
        'checkout' => [
            'ar' => 'إتمام الطلب - أكمل عملية الشراء من زين للعطور بسهولة وأمان.',
            'en' => 'Checkout - Complete your purchase from Zain Perfumes easily and securely.'
        ],
    ];
    return $descriptions[$page][$lang] ?? get_site_description();
}

function get_og_image() {
    return url('assets/img/logo.png');
}

function get_current_url_without_lang() {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = preg_replace('/[?&]lang=(ar|en)/', '', $url);
    $url = str_replace('?&', '?', $url);
    $url = rtrim($url, '?');
    return $url;
}

function get_alternate_lang_url($lang) {
    $url = get_current_url_without_lang();
    $sep = strpos($url, '?') === false ? '?' : '&';
    return $url . $sep . 'lang=' . $lang;
}

function get_base_url() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
}
