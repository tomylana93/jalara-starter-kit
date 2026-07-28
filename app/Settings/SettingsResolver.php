<?php

namespace App\Settings;

use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Settings;

/**
 * Resolves settings classes defensively so the application can still boot
 * before the settings table and its migrations have been run.
 *
 * Only the deployment window - a missing table or a group whose properties
 * have not been migrated yet - is tolerated. Every other failure (connection
 * errors, corrupted payloads, decryption or cast errors) is thrown so it
 * cannot silently degrade into a default value.
 */
final class SettingsResolver
{
    /**
     * Container key memoizing a successful table lookup.
     *
     * The result lives on the container rather than in a static so it cannot
     * outlive the application instance it was measured against.
     */
    private const string AVAILABLE = 'settings.table-available';

    /**
     * Determine whether the settings table exists.
     */
    public static function available(): bool
    {
        if (app()->bound(self::AVAILABLE)) {
            return true;
        }

        $available = Schema::hasTable(config('settings.repositories.database.table', 'settings_properties'));

        if ($available) {
            app()->instance(self::AVAILABLE, true);
        }

        return $available;
    }

    /**
     * Resolve a settings class, or null while its properties are not persisted yet.
     *
     * @template TSettings of Settings
     *
     * @param  class-string<TSettings>  $settings
     * @return TSettings|null
     */
    public static function tryResolve(string $settings): ?Settings
    {
        if (! self::available()) {
            return null;
        }

        try {
            $resolved = app($settings);
            $resolved->toArray();

            return $resolved;
        } catch (MissingSettings) {
            return null;
        }
    }

    /**
     * Forget the memoized availability check.
     */
    public static function flush(): void
    {
        app()->forgetInstance(self::AVAILABLE);
    }
}
