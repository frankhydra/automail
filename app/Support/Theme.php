<?php

namespace App\Support;

use App\Models\User;

/**
 * Small helper around config/themes.php so views and controllers never touch the
 * config array shape directly.
 */
class Theme
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return (array) config('themes.palettes', []);
    }

    public static function default(): string
    {
        return (string) config('themes.default', 'ember');
    }

    public static function isValid(mixed $key): bool
    {
        return is_string($key) && array_key_exists($key, self::all());
    }

    /**
     * The palette key to render for this user. Anything unknown or missing (for example
     * a theme that was later removed from the config) falls back to the default.
     */
    public static function current(?User $user): string
    {
        $saved = $user?->palette;

        return self::isValid($saved) ? $saved : self::default();
    }
}
