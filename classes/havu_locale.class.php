<?php

class HavuLocale
{
    private const DEFAULT_LOCALE = 'fi';

    /** @var array<string, string> */
    private const SUPPORTED_LOCALES = [
        'fi' => 'Suomi',
        'en' => 'English',
        'sv' => 'Svenska',
    ];

    private static bool $initialized = false;
    private static string $currentLocale = self::DEFAULT_LOCALE;

    /** @var array<string, array> */
    private static array $translations = [];

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $locale = self::DEFAULT_LOCALE;

        if (isset($_GET['lang']) && self::isSupported($_GET['lang'])) {
            $locale = $_GET['lang'];
            self::persist($locale);
        } elseif (!empty($_SESSION['locale']) && self::isSupported($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } elseif (!empty($_COOKIE['locale']) && self::isSupported($_COOKIE['locale'])) {
            $locale = $_COOKIE['locale'];
            $_SESSION['locale'] = $locale;
        } else {
            $_SESSION['locale'] = $locale;
        }

        self::$currentLocale = $locale;
        self::$initialized = true;
    }

    public static function current(): string
    {
        self::init();
        return self::$currentLocale;
    }

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * @return array<string, string>
     */
    public static function available(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function translate(string $key, array $params = [], ?string $fallback = null, ?string $locale = null): string
    {
        $activeLocale = $locale ?? self::current();
        $value = self::getByPath(self::loadMergedTranslations($activeLocale), $key);

        if (!is_string($value)) {
            $value = $fallback ?? $key;
        }

        foreach ($params as $name => $replacement) {
            $value = str_replace(':' . $name, (string)$replacement, $value);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function namespace(string $path, ?string $locale = null): array
    {
        $activeLocale = $locale ?? self::current();
        $value = self::getByPath(self::loadMergedTranslations($activeLocale), $path);

        return is_array($value) ? $value : [];
    }

    public static function jsonNamespace(string ...$paths): string
    {
        $data = [];
        foreach ($paths as $path) {
            $data[$path] = self::namespace($path);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadLocale(string $locale): array
    {
        if (!isset(self::$translations[$locale])) {
            $file = __DIR__ . '/../includes/locales/' . $locale . '.php';
            self::$translations[$locale] = file_exists($file) ? require $file : [];
        }

        return self::$translations[$locale];
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadMergedTranslations(string $locale): array
    {
        if ($locale === self::DEFAULT_LOCALE) {
            return self::loadLocale($locale);
        }

        return self::mergeRecursive(self::loadLocale(self::DEFAULT_LOCALE), self::loadLocale($locale));
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private static function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    private static function getByPath(array $data, string $path)
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private static function persist(string $locale): void
    {
        $_SESSION['locale'] = $locale;

        if (!headers_sent()) {
            setcookie('locale', $locale, [
                'expires' => time() + 31536000,
                'path' => defined('ROOT_DIR') ? ROOT_DIR : '/',
                'samesite' => 'Lax',
            ]);
        }
    }
}
