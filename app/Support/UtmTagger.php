<?php

namespace App\Support;

/**
 * Adds Google Analytics style UTM parameters to a link without disturbing it.
 * UTM values the sender already put on a link are never overwritten.
 */
class UtmTagger
{
    /**
     * @param array<string, string> $params e.g. ['utm_source' => 'automail', ...]
     */
    public static function apply(string $url, array $params): string
    {
        $fragment = '';
        if (($pos = strpos($url, '#')) !== false) {
            $fragment = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }

        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $existing);

        $missing = [];
        foreach ($params as $key => $value) {
            if ($value !== '' && !array_key_exists($key, $existing)) {
                $missing[$key] = $value;
            }
        }

        if ($missing === []) {
            return $url.$fragment;
        }

        $separator = str_contains($url, '?') ? (str_ends_with($url, '?') || str_ends_with($url, '&') ? '' : '&') : '?';

        return $url.$separator.http_build_query($missing).$fragment;
    }

    /**
     * True when the link's host is one of the listed domains (or a subdomain of one).
     * An empty list means "every link".
     *
     * @param list<string> $domains
     */
    public static function hostAllowed(string $url, array $domains): bool
    {
        if ($domains === []) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
