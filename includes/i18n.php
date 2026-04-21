<?php

require_once(__DIR__ . '/../classes/havu_locale.class.php');

if (!function_exists('t')) {
    function t(string $key, array $params = [], ?string $fallback = null): string
    {
        return HavuLocale::translate($key, $params, $fallback);
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return HavuLocale::current();
    }
}

if (!function_exists('available_locales')) {
    /**
     * @return array<string, string>
     */
    function available_locales(): array
    {
        return HavuLocale::available();
    }
}

if (!function_exists('locale_url')) {
    function locale_url(string $locale): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? (defined('ROOT_DIR') ? ROOT_DIR : '/');
        $parts = parse_url($requestUri);
        $path = $parts['path'] ?? (defined('ROOT_DIR') ? ROOT_DIR : '/');
        $query = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query['lang'] = $locale;
        $queryString = http_build_query($query);

        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }
}
