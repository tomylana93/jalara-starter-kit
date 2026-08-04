<?php

namespace App\Settings;

use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Settings;

/**
 * Resolves settings classes defensively so the application can still boot
 * before the settings table and its migrations have been run.
 *
 * Only the deployment window - a SQLite file that has not been created yet, a
 * missing table, or a group whose properties have not been migrated yet - is
 * tolerated. Every other failure (connection errors, corrupted payloads,
 * decryption or cast errors) is thrown so it cannot silently degrade into a
 * default value.
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

        if (! self::databaseReachable()) {
            return false;
        }

        $available = Schema::hasTable(config('settings.repositories.database.table', 'settings_properties'));

        if ($available) {
            app()->instance(self::AVAILABLE, true);
        }

        return $available;
    }

    /**
     * Determine whether a file-backed SQLite database has been created yet.
     *
     * The Laravel installer boots the application - to discover packages and
     * to generate the application key - before it creates and migrates the
     * SQLite file. Probing the schema then fails to open the database, which
     * belongs to the same pre-migration window as a missing table rather than
     * to a genuine connection error. Every other driver, an explicit database
     * URL, and in-memory SQLite still reach the connection as before.
     */
    private static function databaseReachable(): bool
    {
        $connection = config('database.default');

        if (config("database.connections.{$connection}.driver") !== 'sqlite') {
            return true;
        }

        if (config("database.connections.{$connection}.url") !== null) {
            return true;
        }

        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || $database === '' || $database === ':memory:') {
            return true;
        }

        return is_file($database);
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
