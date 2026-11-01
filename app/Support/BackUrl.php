<?php

namespace App\Support;

/**
 * Works out where a page's "Back" arrow should go.
 *
 * The browser's Referer is the page you just came from, not the page "above" this one, so using it
 * for a back link makes two pages point at each other (create -> show -> create ...) or a page point
 * at itself after a redirect. Instead, pages that link here pass their own address in `?from=`, and
 * this validates it: same site only, and never a page that would trap the user in a loop.
 */
class BackUrl
{
    /**
     * @param  string  $fallback  Where to go when there is no usable origin.
     * @return string A same-site path (with query string and tab fragment), or the fallback.
     */
    public static function resolve(mixed $candidate, string $fallback): string
    {
        if (! is_string($candidate) || $candidate === '' || strlen($candidate) > 2000) {
            return $fallback;
        }

        // Backslashes and control characters are read as slashes by some browsers ("/\evil.com").
        if (preg_match('/[\\\\\x00-\x1F\x7F]/', $candidate)) {
            return $fallback;
        }

        $parts = parse_url($candidate);

        if ($parts === false) {
            return $fallback;
        }

        if (isset($parts['host'])) {
            if (strcasecmp($parts['host'], request()->getHost()) !== 0) {
                return $fallback;
            }
        } elseif (isset($parts['scheme'])) {
            return $fallback; // javascript:, data:, mailto: ...
        }

        $path = $parts['path'] ?? '/';

        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        if (self::isLoopPage($path)) {
            return $fallback;
        }

        $url = $path;

        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?' . $parts['query'];
        }

        if (isset($parts['fragment']) && preg_match('/^[A-Za-z0-9_-]+$/', $parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /** Pages that must never be a back target: the current page, the order form, an order's details. */
    private static function isLoopPage(string $path): bool
    {
        $path = '/' . trim($path, '/');
        $current = '/' . trim(request()->path(), '/');

        return $path === $current
            || $path === '/transactions/create'
            || preg_match('#^/transactions/\d+$#', $path) === 1
            || in_array($path, ['/login', '/logout'], true);
    }
}
