<?php

class YouTubeEmbed
{
    private const EMBED_HOST = 'https://www.youtube-nocookie.com/embed/';

    public static function normalizeHtml(?string $html): string
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $rootId = 'havu-youtube-root';

        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="' . $rootId . '">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
            return $html;
        }

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//iframe') as $iframe) {
            if (!$iframe instanceof DOMElement) {
                continue;
            }

            $embedData = self::extractEmbedData($iframe->getAttribute('src'));
            if ($embedData === null) {
                continue;
            }

            $replacementTarget = $iframe;
            $parent = $iframe->parentNode;
            if ($parent instanceof DOMElement && $parent->getAttribute('class') === 'node-rich-content__embed') {
                $replacementTarget = $parent;
            }

            $replacement = self::buildEmbedNode($document, $embedData['videoId'], $embedData['start']);
            if ($replacementTarget->parentNode !== null) {
                $replacementTarget->parentNode->replaceChild($replacement, $replacementTarget);
            }
        }

        $root = $document->getElementById($rootId);
        $normalizedHtml = '';
        if ($root !== null) {
            foreach ($root->childNodes as $childNode) {
                $normalizedHtml .= $document->saveHTML($childNode);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return trim($normalizedHtml);
    }

    public static function extractVideoId(string $input): ?string
    {
        $embedData = self::extractEmbedData($input);
        return $embedData['videoId'] ?? null;
    }

    /**
     * @return array{videoId: string, start: int}|null
     */
    private static function extractEmbedData(string $input): ?array
    {
        $input = trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($input === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input) === 1) {
            return [
                'videoId' => $input,
                'start' => 0,
            ];
        }

        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $input, $matches) === 1) {
            $input = $matches[1];
        }

        if (preg_match('~^https?://~i', $input) !== 1) {
            return null;
        }

        $urlParts = parse_url($input);
        if ($urlParts === false) {
            return null;
        }

        $host = strtolower($urlParts['host'] ?? '');
        if (substr($host, 0, 4) === 'www.') {
            $host = substr($host, 4);
        }

        $path = trim($urlParts['path'] ?? '', '/');
        $pathSegments = $path === '' ? [] : explode('/', $path);

        parse_str($urlParts['query'] ?? '', $queryParams);

        $videoId = null;
        if ($host === 'youtu.be') {
            $videoId = $pathSegments[0] ?? null;
        } elseif ($host === 'youtube.com' || $host === 'm.youtube.com' || $host === 'youtube-nocookie.com') {
            if (!empty($queryParams['v'])) {
                $videoId = $queryParams['v'];
            } elseif (($pathSegments[0] ?? '') === 'embed' && !empty($pathSegments[1])) {
                $videoId = $pathSegments[1];
            } elseif (($pathSegments[0] ?? '') === 'shorts' && !empty($pathSegments[1])) {
                $videoId = $pathSegments[1];
            } elseif (($pathSegments[0] ?? '') === 'live' && !empty($pathSegments[1])) {
                $videoId = $pathSegments[1];
            }
        }

        if (!is_string($videoId) || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            return null;
        }

        $start = 0;
        if (!empty($queryParams['start'])) {
            $start = self::parseStartOffset((string)$queryParams['start']);
        } elseif (!empty($queryParams['t'])) {
            $start = self::parseStartOffset((string)$queryParams['t']);
        }

        if (!empty($urlParts['fragment'])) {
            parse_str((string)$urlParts['fragment'], $fragmentParams);
            if (!empty($fragmentParams['t'])) {
                $start = self::parseStartOffset((string)$fragmentParams['t']);
            }
        }

        return [
            'videoId' => $videoId,
            'start' => $start,
        ];
    }

    private static function parseStartOffset(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return (int)$value;
        }

        if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $matches) !== 1) {
            return 0;
        }

        return ((int)($matches[1] ?? 0) * 3600)
            + ((int)($matches[2] ?? 0) * 60)
            + (int)($matches[3] ?? 0);
    }

    private static function buildEmbedNode(DOMDocument $document, string $videoId, int $start): DOMElement
    {
        $wrapper = $document->createElement('div');
        $wrapper->setAttribute('class', 'node-rich-content__embed');
        $wrapper->setAttribute('data-embed-provider', 'youtube');

        $iframe = $document->createElement('iframe');
        $iframe->setAttribute('src', self::buildEmbedUrl($videoId, $start));
        $iframe->setAttribute('title', 'YouTube video player');
        $iframe->setAttribute('loading', 'lazy');
        $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        $iframe->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        $iframe->setAttribute('allowfullscreen', '');

        $wrapper->appendChild($iframe);

        return $wrapper;
    }

    private static function buildEmbedUrl(string $videoId, int $start): string
    {
        $embedUrl = self::EMBED_HOST . rawurlencode($videoId);
        if ($start > 0) {
            $embedUrl .= '?start=' . $start;
        }

        return $embedUrl;
    }
}
