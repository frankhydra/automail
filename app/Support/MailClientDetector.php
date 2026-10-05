<?php

namespace App\Support;

/**
 * Best-effort guess of which mail app opened an email, from the User-Agent of
 * the tracking-pixel request. This is a rough signal, not a measurement: mail
 * privacy proxies hide the real app, so the Analytics page labels it as such.
 */
class MailClientDetector
{
    public static function detect(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        if ($ua === '') {
            return 'Other';
        }

        // Gmail fetches images through Google's image proxy.
        if (stripos($ua, 'GoogleImageProxy') !== false) {
            return 'Gmail';
        }

        if (stripos($ua, 'YahooMailProxy') !== false || stripos($ua, 'Yahoo') !== false) {
            return 'Yahoo Mail';
        }

        if (stripos($ua, 'Outlook') !== false || stripos($ua, 'ms-office') !== false || stripos($ua, 'Microsoft Office') !== false) {
            return 'Outlook';
        }

        // Apple Mail (and Apple's Mail Privacy Protection) identify as WebKit without "Safari".
        if (stripos($ua, 'AppleWebKit') !== false
            && stripos($ua, 'Safari') === false
            && preg_match('/iPhone|iPad|Macintosh|Mac OS X/i', $ua)) {
            return 'Apple Mail';
        }

        return 'Other';
    }
}
