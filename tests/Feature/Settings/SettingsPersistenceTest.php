<?php

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\ColorThemePreset;
use App\Enums\DateFormat;
use App\Enums\FontPairPreset;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Settings\AuthenticationSettings;
use App\Settings\BrandingSettings;
use App\Settings\ChatSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use App\Settings\UserProvisioningSettings;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Schema;

it('stores settings in the settings properties table', function () {
    expect(Schema::hasTable('settings_properties'))->toBeTrue()
        ->and(Schema::hasColumns('settings_properties', ['group', 'name', 'locked', 'payload']))->toBeTrue();
});

it('registers every settings class explicitly', function () {
    expect(config('settings.settings'))->toBe([
        GeneralSettings::class,
        AuthenticationSettings::class,
        MailSettings::class,
        UserProvisioningSettings::class,
        SecuritySettings::class,
        BrandingSettings::class,
        ChatSettings::class,
    ])->and(config('settings.auto_discover_settings'))->toBe([]);
});

it('resolves every settings class with its initial values', function () {
    expect(app(GeneralSettings::class)->applicationName)->toBe(config('app.name'))
        ->and(app(GeneralSettings::class)->description)->toBe(config('app.description'))
        ->and(app(GeneralSettings::class)->defaultLocale)->toBe(Locale::English)
        ->and(app(GeneralSettings::class)->dateFormat)->toBe(DateFormat::DayShortMonthYear)
        ->and(app(AuthenticationSettings::class)->requireEmailVerification)->toBeTrue()
        ->and(app(AuthenticationSettings::class)->sessionLifetimeMinutes)->toBe(120)
        ->and(app(MailSettings::class)->fromAddress)->toBe(config('mail.from.address'))
        ->and(app(UserProvisioningSettings::class)->defaultPassword)->toBeNull()
        ->and(app(SecuritySettings::class)->maxFailedLoginAttempts)->toBe(5)
        ->and(app(SecuritySettings::class)->suspensionDurationMinutes)->toBe(15)
        ->and(app(SecuritySettings::class)->maintenanceEnabled)->toBeFalse()
        ->and(app(BrandingSettings::class)->authLayout)->toBe(AuthLayoutPreset::Simple)
        ->and(app(BrandingSettings::class)->appLayout)->toBe(AppLayoutPreset::Sidebar)
        ->and(app(BrandingSettings::class)->colorTheme)->toBe(ColorThemePreset::Neutral)
        ->and(app(BrandingSettings::class)->fontPair)->toBe(FontPairPreset::InstrumentSans)
        ->and(app(BrandingSettings::class)->footerText)->toBe('© Jalara. All rights reserved.');
});

it('seeds a complete Jalara identity rather than a framework placeholder', function () {
    $general = app(GeneralSettings::class);
    $branding = app(BrandingSettings::class);
    $mail = app(MailSettings::class);

    expect($general->applicationName)->toBe('Jalara')
        ->and($general->description)->toBe('Jalara Starter Kit')
        ->and($branding->companyName)->toBe('Jalara')
        ->and($branding->footerText)->toBe('© Jalara. All rights reserved.')
        ->and($mail->fromName)->toBe('Jalara')
        ->and($mail->fromAddress)->toBe('hello@jalara.dev');

    foreach ([$general->applicationName, $general->description, $branding->companyName, $branding->footerText, $mail->fromName, $mail->fromAddress] as $value) {
        expect($value)->not->toBeEmpty()
            ->and($value)->not->toContain('Laravel')
            ->and($value)->not->toContain('example.com');
    }
});

it('leaves the provisioning secret and every uploaded asset path unseeded', function () {
    $branding = app(BrandingSettings::class);

    expect(app(UserProvisioningSettings::class)->defaultPassword)->toBeNull()
        ->and($branding->logoPath)->toBeNull()
        ->and($branding->logoDarkPath)->toBeNull()
        ->and($branding->iconPath)->toBeNull()
        ->and($branding->iconDarkPath)->toBeNull()
        ->and($branding->authBackgroundPath)->toBeNull();
});

it('defaults the password policy to the strict preset', function () {
    expect(app(AuthenticationSettings::class)->refresh()->passwordPolicy)->toBe(PasswordPolicy::Strict);
});

it('casts enum, boolean, and integer values across save and reload', function () {
    $general = app(GeneralSettings::class);
    $general->defaultLocale = Locale::Indonesian;
    $general->dateFormat = DateFormat::Iso;
    $general->save();

    $security = app(SecuritySettings::class);
    $security->maintenanceEnabled = true;
    $security->maxFailedLoginAttempts = 9;
    $security->save();

    expect(app(GeneralSettings::class)->refresh()->defaultLocale)->toBe(Locale::Indonesian)
        ->and(app(GeneralSettings::class)->refresh()->dateFormat)->toBe(DateFormat::Iso)
        ->and(app(SecuritySettings::class)->refresh()->maintenanceEnabled)->toBeTrue()
        ->and(app(SecuritySettings::class)->refresh()->maxFailedLoginAttempts)->toBe(9);
});

it('disables the settings cache during tests while remaining configurable', function () {
    expect(config('settings.cache.enabled'))->toBeFalse()
        ->and(config('settings.cache.prefix'))->toBe('settings');

    $configuration = require config_path('settings.php');

    expect($configuration['cache']['enabled'])
        ->toBeFalse()
        ->toBe((bool) Env::get('SETTINGS_CACHE_ENABLED', false));
});
