<?php

use App\Enums\Locale;
use App\Settings\GeneralSettings;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Drop the resolved general settings singleton so the next resolution goes
 * through the cache rather than the container.
 */
function forgetResolvedGeneralSettings(): void
{
    app()->forgetInstance(GeneralSettings::class);
}

/**
 * How often the given callback read a settings group from its table.
 *
 * Counting the settings table specifically keeps the assertion about settings
 * resolution rather than about whatever else a callback happens to touch.
 */
function settingsQueryCount(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    return count(array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'settings_properties'),
    ));
}

it('reads a settings group from the database only once while cached', function () {
    expect(config('settings.cache.enabled'))->toBeTrue()
        ->and(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));

    forgetResolvedGeneralSettings();

    $queries = settingsQueryCount(function (): void {
        expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));
    });

    expect($queries)->toBe(0);
});

it('stores the resolved group in the cache store itself', function () {
    expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));

    /*
     * Asserted against the store rather than through a resolution: a zero-query
     * resolution alone would also be satisfied by the per-request memo, which
     * does not survive the request that filled it.
     */
    expect(Cache::get('settings.settings.'.GeneralSettings::cacheKey()))->not->toBeNull();
});

it('serves the saved value after a settings group is updated', function () {
    $general = app(GeneralSettings::class);
    $general->defaultLocale = Locale::Indonesian;
    $general->save();

    forgetResolvedGeneralSettings();

    $queries = settingsQueryCount(function (): void {
        expect(app(GeneralSettings::class)->defaultLocale)->toBe(Locale::Indonesian);
    });

    expect($queries)->toBe(0);
});

it('drops the cached group when migrations end', function () {
    expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));

    forgetResolvedGeneralSettings();

    Event::dispatch(new MigrationsEnded('up'));

    $queries = settingsQueryCount(function (): void {
        expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));
    });

    expect($queries)->toBeGreaterThan(0);
});

it('ships the cache enabled to operators', function () {
    $template = file_get_contents(base_path('.env.example'));

    expect($template)->toContain('SETTINGS_CACHE_ENABLED=true')
        ->toContain('SETTINGS_CACHE_MEMO=true');
});
