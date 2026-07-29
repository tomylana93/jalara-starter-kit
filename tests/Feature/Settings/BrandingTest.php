<?php

use App\Actions\Settings\UpdateBrandingSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\ColorThemePreset;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

function updateBranding(array $overrides = []): void
{
    app(UpdateBrandingSettings::class)->handle(app(BrandingSettings::class), array_merge([
        'companyName' => 'Jalara Group',
        'footerText' => null,
        'identityMode' => 'icon-text',
        'authLayout' => 'simple',
        'appLayout' => 'sidebar',
        'colorTheme' => 'neutral',
        'fontPreset' => 'instrument-sans',
    ], $overrides));
}

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

it('shares the branding presets as string values', function () {
    updateBranding([
        'footerText' => 'All rights reserved.',
        'authLayout' => 'split',
        'appLayout' => 'header',
        'colorTheme' => 'emerald',
        'fontPreset' => 'system-serif',
    ]);

    get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->where('branding.companyName', 'Jalara Group')
        ->where('branding.footerText', 'All rights reserved.')
        ->where('branding.authLayout', 'split')
        ->where('branding.appLayout', 'header')
        ->where('branding.colorTheme', 'emerald')
        ->where('branding.fontPreset', 'system-serif'),
    );
});

it('shares scalars rather than settings or enum objects', function () {
    $branding = get(route('login'))->viewData('page')['props']['branding'];

    expect($branding)->toBeArray()
        ->and($branding)->toHaveKeys([
            'companyName', 'footerText', 'identityMode', 'authLayout', 'appLayout', 'colorTheme', 'fontPreset',
            'logoUrl', 'logoDarkUrl', 'iconUrl', 'iconDarkUrl', 'authBackgroundUrl',
        ]);

    foreach (['authLayout', 'appLayout', 'colorTheme', 'fontPreset'] as $preset) {
        expect($branding[$preset])->toBeString();
    }
});

it('renders the branding attributes on the root element', function () {
    updateBranding(['colorTheme' => 'violet', 'fontPreset' => 'system-mono']);

    get(route('login'))
        ->assertSee('data-color-theme="violet"', false)
        ->assertSee('data-font-preset="system-mono"', false);
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
        ->assertSee('<title>Jalara App</title>', false)
        ->assertDontSee('<title>Jalara Group</title>', false);

    updateBranding(['companyName' => 'Renamed Company']);

    get(route('login'))
        ->assertSee('<title>Jalara App</title>', false)
        ->assertDontSee('<title>Renamed Company</title>', false);
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

it('renders the default branding attributes before the settings are persisted', function () {
    get(route('login'))
        ->assertSee('data-color-theme="neutral"', false)
        ->assertSee('data-font-preset="instrument-sans"', false);
});
