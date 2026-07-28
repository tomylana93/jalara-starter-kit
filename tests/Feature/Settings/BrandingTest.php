<?php

use App\Actions\Settings\UpdateBrandingSettings;
use App\Settings\BrandingSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

function updateBranding(array $overrides = []): void
{
    app(UpdateBrandingSettings::class)->handle(app(BrandingSettings::class), array_merge([
        'companyName' => 'Jalara Group',
        'footerText' => null,
        'authLayout' => 'simple',
        'appLayout' => 'sidebar',
        'colorTheme' => 'neutral',
        'fontPreset' => 'instrument-sans',
    ], $overrides));
}

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
            'companyName', 'footerText', 'authLayout', 'appLayout', 'colorTheme', 'fontPreset',
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

it('renders the company name as the document title', function () {
    updateBranding(['companyName' => 'Jalara Group']);

    get(route('login'))->assertSee('<title>Jalara Group</title>', false);

    updateBranding(['companyName' => 'Renamed Company']);

    get(route('login'))
        ->assertSee('<title>Renamed Company</title>', false)
        ->assertDontSee('Jalara Group', false);
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
