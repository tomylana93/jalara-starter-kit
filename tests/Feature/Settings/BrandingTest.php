<?php

use App\Actions\Settings\UpdateBrandingSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\ColorThemePreset;
use App\Enums\FontPairPreset;
use App\Http\Presenters\BrandingPresenter;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

/**
 * @param  array<string, mixed>  $overrides
 */
function updateBranding(array $overrides = []): void
{
    app(UpdateBrandingSettings::class)->handle(app(BrandingSettings::class), array_merge([
        'companyName' => 'Jalara Group',
        'footerText' => null,
        'identityMode' => 'icon-text',
        'authLayout' => 'simple',
        'appLayout' => 'sidebar',
        'colorTheme' => 'neutral',
        'fontPair' => 'instrument-sans',
    ], $overrides));
}

it('falls back to the Jalara identity before the branding settings resolve', function () {
    $defaults = BrandingPresenter::defaults();

    expect($defaults['companyName'])->toBe('Jalara')
        ->and($defaults['footerText'])->toBe('© Jalara. All rights reserved.')
        ->and($defaults['logoUrl'])->toBeNull()
        ->and($defaults['logoDarkUrl'])->toBeNull()
        ->and($defaults['iconUrl'])->toBeNull()
        ->and($defaults['iconDarkUrl'])->toBeNull()
        ->and($defaults['authBackgroundUrl'])->toBeNull();
});

it('lets stored branding override the fallback identity', function () {
    updateBranding(['companyName' => 'Jalara Group', 'footerText' => 'Copyright Jalara Group.']);

    $branding = get(route('login'))->viewData('page')['props']['branding'];

    expect($branding['companyName'])->toBe('Jalara Group')
        ->and($branding['footerText'])->toBe('Copyright Jalara Group.')
        ->and($branding)->not->toBe(BrandingPresenter::defaults());
});

it('exposes all supported color theme presets', function () {
    expect(array_column(ColorThemePreset::options(), 'value'))->toBe([
        'neutral',
        'blue',
        'emerald',
        'violet',
        'rose',
        'amber',
        'teal',
        'cyan',
        'indigo',
        'orange',
    ]);
});

it('exposes common heading and body font pairs', function () {
    expect(array_column(FontPairPreset::options(), 'value'))->toBe([
        'instrument-sans',
        'space-grotesk-inter',
        'poppins-inter',
        'montserrat-open-sans',
        'playfair-display-source-sans',
    ]);
});

it('shares the branding presets as string values', function () {
    updateBranding([
        'footerText' => 'All rights reserved.',
        'authLayout' => 'split',
        'appLayout' => 'header',
        'colorTheme' => 'emerald',
        'fontPair' => 'playfair-display-source-sans',
    ]);

    get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('branding.companyName', 'Jalara Group')
        ->where('branding.footerText', 'All rights reserved.')
        ->where('branding.authLayout', 'split')
        ->where('branding.appLayout', 'header')
        ->where('branding.colorTheme', 'emerald')
        ->where('branding.fontPair', 'playfair-display-source-sans'),
    );
});

it('shares scalars rather than settings or enum objects', function () {
    $branding = get(route('login'))->viewData('page')['props']['branding'];

    expect($branding)->toBeArray()
        ->and($branding)->toHaveKeys([
            'companyName', 'footerText', 'identityMode', 'authLayout', 'appLayout', 'colorTheme', 'fontPair',
            'logoUrl', 'logoDarkUrl', 'iconUrl', 'iconDarkUrl', 'authBackgroundUrl',
        ]);

    foreach (['authLayout', 'appLayout', 'colorTheme', 'fontPair'] as $preset) {
        expect($branding[$preset])->toBeString();
    }
});

it('renders the branding attributes on the root element', function () {
    updateBranding(['colorTheme' => 'violet', 'fontPair' => 'space-grotesk-inter']);

    get(route('login'))
        ->assertSee('data-color-theme="violet"', false)
        ->assertSee('data-font-pair="space-grotesk-inter"', false);
});

it('renders the application name as the document title independently of branding', function () {
    $general = app(GeneralSettings::class);

    app(UpdateGeneralSettings::class)->handle($general, [
        'applicationName' => 'Jalara App',
        'description' => $general->description,
        'defaultLocale' => $general->defaultLocale->value,
        'dateFormat' => $general->dateFormat->value,
    ]);

    updateBranding(['companyName' => 'Jalara Group']);

    get(route('login'))
        ->assertSee('Jalara App</title>', false)
        ->assertDontSee('Jalara Group</title>', false);

    updateBranding(['companyName' => 'Renamed Company']);

    get(route('login'))
        ->assertSee('Jalara App</title>', false)
        ->assertDontSee('Renamed Company</title>', false);
});

it('shares the application name and description with the auth layouts', function () {
    $general = app(GeneralSettings::class);

    app(UpdateGeneralSettings::class)->handle($general, [
        'applicationName' => 'Jalara App',
        'description' => 'Operational starter kit',
        'defaultLocale' => $general->defaultLocale->value,
        'dateFormat' => $general->dateFormat->value,
    ]);

    get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('name', 'Jalara App')
        ->where('description', 'Operational starter kit'),
    );
});

it('shares the footer text with the layouts', function () {
    updateBranding(['footerText' => 'Copyright Jalara Group.']);

    get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('branding.footerText', 'Copyright Jalara Group.'),
    );
});

it('shares the released application version alongside the footer text', function () {
    updateBranding(['footerText' => null]);

    get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('version', config('app.version'))
        ->where('branding.footerText', null),
    );

    expect(config('app.version'))->toBe(
        json_decode((string) file_get_contents(base_path('version.json')), true)['version'],
    );
});

it('renders the default branding attributes before the settings are persisted', function () {
    get(route('login'))
        ->assertSee('data-color-theme="neutral"', false)
        ->assertSee('data-font-pair="instrument-sans"', false);
});
