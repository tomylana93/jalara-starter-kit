<?php

use App\Enums\ColorThemePreset;
use App\Enums\DateFormat;
use App\Enums\FontPreset;
use App\Enums\PasswordPolicy;
use App\Models\User;
use App\Settings\AuthenticationSettings;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use App\Settings\UserProvisioningSettings;

use function Pest\Laravel\actingAs;

pest()->group('browser');

it('renders every settings screen for a settings manager', function () {
    actingAs(settingsManager());

    visit([
        route('settings.index', absolute: false),
        route('settings.general.edit', absolute: false),
        route('settings.authentication.edit', absolute: false),
        route('settings.user-provisioning.edit', absolute: false),
        route('settings.mail.edit', absolute: false),
        route('settings.security.edit', absolute: false),
        route('settings.branding.edit', absolute: false),
    ])->assertNoSmoke();
});

it('navigates from the settings index to a settings screen', function () {
    actingAs(settingsManager());

    visit(route('settings.index', absolute: false))
        ->assertSee(__('setting.layout.title'))
        ->assertSee(__('setting.general.title'))
        ->assertSee(__('setting.authentication.title'))
        ->assertSee(__('setting.user_provisioning.title'))
        ->assertSee(__('setting.mail.title'))
        ->assertSee(__('setting.security.title'))
        ->assertSee(__('setting.branding.title'))
        ->click('@settings-card-general')
        ->assertPathIs(route('settings.general.edit', absolute: false))
        ->assertNoSmoke();
});

it('hides settings navigation from other users', function () {
    actingAs(User::factory()->create());

    visit(route('dashboard', absolute: false))
        ->assertDontSee(__('navigation.main.settings'))
        ->assertNoSmoke();
});

it('submits a select value through its hidden input', function () {
    actingAs(settingsManager());

    visit(route('settings.general.edit', absolute: false))
        ->fill('#applicationName', 'Jalara App')
        ->click('#dateFormat')
        ->click('@date-format-option-'.DateFormat::Iso->value)
        ->click('@update-general-settings-button')
        ->assertSee(__('setting.general.message.updated'))
        ->assertTitleContains('Jalara App')
        ->assertNoSmoke();

    expect(app(GeneralSettings::class)->refresh()->dateFormat)->toBe(DateFormat::Iso);
});

it('precognizes an invalid setting before the form is submitted', function () {
    actingAs(settingsManager());

    visit(route('settings.general.edit', absolute: false))
        ->clear('#applicationName')
        ->click('#dateFormat')
        ->assertSee(
            __('validation.required', ['attribute' => 'application name']),
        )
        ->assertNoSmoke();

    expect(app(GeneralSettings::class)->refresh()->applicationName)->not->toBe('');
});

it('submits a false switch value through its hidden input', function () {
    $authentication = app(AuthenticationSettings::class);
    $authentication->requireEmailVerification = true;
    $authentication->passwordPolicy = PasswordPolicy::Basic;
    $authentication->save();

    actingAs(settingsManager());

    visit(route('settings.authentication.edit', absolute: false))
        ->click('#requireEmailVerification')
        ->click('@update-authentication-settings-button')
        ->assertSee(__('setting.authentication.message.updated'))
        ->assertNoSmoke();

    expect(app(AuthenticationSettings::class)->refresh()->requireEmailVerification)->toBeFalse();
});

it('submits branding radio groups and applies the stored theme', function () {
    actingAs(settingsManager());

    visit(route('settings.branding.edit', absolute: false))
        ->fill('#companyName', 'Jalara Group')
        ->click('#colorTheme-emerald')
        ->click('#fontPreset-system-serif')
        ->click('#appLayout-header')
        ->click('@update-branding-settings-button')
        ->assertSee(__('setting.branding.message.updated'))
        ->assertDataAttribute(':root', 'color-theme', 'emerald')
        ->assertDataAttribute(':root', 'font-preset', 'system-serif')
        ->assertNoSmoke();
});

it('applies the stored branding theme in dark mode', function () {
    $branding = app(BrandingSettings::class);
    $branding->companyName = 'Jalara Group';
    $branding->colorTheme = ColorThemePreset::Emerald;
    $branding->fontPreset = FontPreset::SystemSerif;
    $branding->save();

    actingAs(settingsManager());

    visit(route('settings.branding.edit', absolute: false))
        ->inDarkMode()
        ->assertDataAttribute(':root', 'color-theme', 'emerald')
        ->assertDataAttribute(':root', 'font-preset', 'system-serif')
        ->assertNoSmoke();
});

it('updates and then removes the default password through the dialog', function () {
    actingAs(settingsManager());

    $page = visit(route('settings.user-provisioning.edit', absolute: false))
        ->assertSee(__('setting.user_provisioning.status.not_configured'))
        ->fill('#defaultPassword', 'Jalara-Def4ult!')
        ->fill('#defaultPassword_confirmation', 'Jalara-Def4ult!')
        ->click('@update-default-password-button')
        ->assertSee(__('setting.user_provisioning.status.configured'))
        ->assertNoSmoke();

    expect(app(UserProvisioningSettings::class)->refresh()->hasDefaultPassword())->toBeTrue();

    $page
        ->click('@remove-default-password-button')
        ->assertSee(__('setting.user_provisioning.confirmation.title'))
        ->click('@confirm-remove-default-password-button')
        ->assertSee(__('setting.user_provisioning.status.not_configured'))
        ->assertNoSmoke();

    expect(app(UserProvisioningSettings::class)->refresh()->hasDefaultPassword())->toBeFalse();
});
