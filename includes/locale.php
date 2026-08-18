<?php
declare(strict_types=1);

require_once __DIR__ . '/translations.php';

function locale_bootstrap(): void
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    } elseif (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'ar'], true)) {
        $_SESSION['lang'] = $_POST['lang'];
    }
    if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'ar'], true)) {
        $_SESSION['lang'] = 'ar';
    }
}

function current_lang(): string
{
    return $_SESSION['lang'] ?? 'ar';
}

function is_rtl(): bool
{
    return current_lang() === 'ar';
}

function t(string $key, array $replace = []): string
{
    $lang = current_lang();
    $map = translation_map();
    $str = $map[$lang][$key] ?? $map['en'][$key] ?? $map['ar'][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $str = str_replace(':' . $k, (string) $v, $str);
    }
    return $str;
}

function site_name(): string
{
    return t('site_name');
}

function site_tagline(): string
{
    return t('site_tagline');
}

function lang_switch_url(string $lang): string
{
    if (!in_array($lang, ['en', 'ar'], true)) {
        $lang = 'en';
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    parse_str($parts['query'] ?? '', $q);
    /* Always set lang so the session updates (unset alone left Arabic active when switching to English). */
    $q['lang'] = $lang;
    $query = http_build_query($q);
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    return $path . ($query !== '' ? '?' . $query : '') . $fragment;
}
