<?php

use App\Enums\Locale;
use App\Settings\GeneralSettings;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Drop the resolved settings singleton so the next resolution goes through the
 * cache rather than the container.
 */
function forgetResolvedSettings(): void
{
    app()->forgetInstance(GeneralSettings::class);
}

/**
 * Count the queries a callback runs.
 */
function queriesDuring(Closure $callback): int
{
    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $callback();

    return $queries;
}

it('reads a settings group from the database only once while cached', function () {
    expect(config('settings.cache.enabled'))->toBeTrue()
        ->and(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));

    forgetResolvedSettings();

    $queries = queriesDuring(function (): void {
        expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));
    });

    expect($queries)->toBe(0);
});

it('serves the saved value after a settings group is updated', function () {
    $general = app(GeneralSettings::class);
    $general->defaultLocale = Locale::Indonesian;
    $general->save();

    forgetResolvedSettings();

    $queries = queriesDuring(function (): void {
        expect(app(GeneralSettings::class)->defaultLocale)->toBe(Locale::Indonesian);
    });

    expect($queries)->toBe(0);
});

it('drops the cached group when migrations end', function () {
    expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));

    forgetResolvedSettings();

    Event::dispatch(new MigrationsEnded('up'));

    $queries = queriesDuring(function (): void {
        expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'));
    });

    expect($queries)->toBeGreaterThan(0);
});

it('ships the cache enabled to operators', function () {
    $template = file_get_contents(base_path('.env.example'));

    expect($template)->toContain('SETTINGS_CACHE_ENABLED=true')
        ->toContain('SETTINGS_CACHE_MEMO=true');
});
