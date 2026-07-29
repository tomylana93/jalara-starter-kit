<?php

use App\Actions\Settings\SendTestMail;
use App\Actions\Settings\UpdateAuthenticationSettings;
use App\Actions\Settings\UpdateBrandingSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Actions\Settings\UpdateMailSettings;
use App\Actions\Settings\UpdateSecuritySettings;
use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\DateFormat;
use App\Enums\FontPairPreset;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Mail\TestMailConfiguration;
use App\Models\User;
use App\Settings\AuthenticationSettings;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Mail;

it('persists the general settings', function () {
    app(UpdateGeneralSettings::class)->handle(app(GeneralSettings::class), [
        'applicationName' => 'Jalara',
        'description' => 'Starter kit',
        'defaultLocale' => 'id',
        'dateFormat' => 'Y-m-d',
    ]);

    $settings = app(GeneralSettings::class)->refresh();

    expect($settings->applicationName)->toBe('Jalara')
        ->and($settings->description)->toBe('Starter kit')
        ->and($settings->defaultLocale)->toBe(Locale::Indonesian)
        ->and($settings->dateFormat)->toBe(DateFormat::Iso)
        ->and(config('app.name'))->toBe('Jalara')
        ->and(config('app.description'))->toBe('Starter kit')
        ->and(config('app.locale'))->toBe(Locale::Indonesian->value)
        ->and(app()->getLocale())->toBe(Locale::Indonesian->value);
});

it('persists an empty general description', function () {
    app(UpdateGeneralSettings::class)->handle(app(GeneralSettings::class), [
        'applicationName' => 'Jalara',
        'description' => null,
        'defaultLocale' => 'en',
        'dateFormat' => 'd M Y',
    ]);

    expect(app(GeneralSettings::class)->refresh()->description)->toBeNull();
});

it('persists the authentication settings', function () {
    app(UpdateAuthenticationSettings::class)->handle(app(AuthenticationSettings::class), [
        'requireEmailVerification' => false,
        'passwordPolicy' => 'standard',
        'sessionLifetimeMinutes' => 480,
    ]);

    $settings = app(AuthenticationSettings::class)->refresh();

    expect($settings->requireEmailVerification)->toBeFalse()
        ->and($settings->passwordPolicy)->toBe(PasswordPolicy::Standard)
        ->and($settings->sessionLifetimeMinutes)->toBe(480);
});

it('persists the mail settings', function () {
    app(UpdateMailSettings::class)->handle(app(MailSettings::class), [
        'fromName' => 'Jalara Support',
        'fromAddress' => 'support@jalara.test',
    ]);

    $settings = app(MailSettings::class)->refresh();

    expect($settings->fromName)->toBe('Jalara Support')
        ->and($settings->fromAddress)->toBe('support@jalara.test');
});

it('persists the security settings', function () {
    app(UpdateSecuritySettings::class)->handle(app(SecuritySettings::class), [
        'maxFailedLoginAttempts' => 3,
        'suspensionDurationMinutes' => 60,
        'maintenanceEnabled' => true,
    ]);

    $settings = app(SecuritySettings::class)->refresh();

    expect($settings->maxFailedLoginAttempts)->toBe(3)
        ->and($settings->suspensionDurationMinutes)->toBe(60)
        ->and($settings->maintenanceEnabled)->toBeTrue();
});

it('persists the branding settings', function () {
    app(UpdateBrandingSettings::class)->handle(app(BrandingSettings::class), [
        'companyName' => 'Jalara Group',
        'footerText' => 'All rights reserved.',
        'identityMode' => 'logo',
        'authLayout' => 'split',
        'appLayout' => 'header',
        'colorTheme' => 'violet',
        'fontPair' => 'space-grotesk-inter',
    ]);

    $settings = app(BrandingSettings::class)->refresh();

    expect($settings->companyName)->toBe('Jalara Group')
        ->and($settings->footerText)->toBe('All rights reserved.')
        ->and($settings->identityMode)->toBe(BrandingIdentityMode::Logo)
        ->and($settings->authLayout)->toBe(AuthLayoutPreset::Split)
        ->and($settings->appLayout)->toBe(AppLayoutPreset::Header)
        ->and($settings->colorTheme)->toBe(ColorThemePreset::Violet)
        ->and($settings->fontPair)->toBe(FontPairPreset::SpaceGroteskInter);
});

it('sends the test message to the managing user with the configured identity', function () {
    Mail::fake();

    $manager = settingsManager();

    app(UpdateMailSettings::class)->handle(app(MailSettings::class), [
        'fromName' => 'Jalara Support',
        'fromAddress' => 'support@jalara.test',
    ]);

    app(SendTestMail::class)->handle($manager, app(MailSettings::class), app(BrandingSettings::class));

    Mail::assertSent(TestMailConfiguration::class, function (TestMailConfiguration $mail) use ($manager): bool {
        $from = $mail->envelope()->from;

        return $mail->hasTo($manager->email)
            && count($mail->to) === 1
            && $from?->address === 'support@jalara.test'
            && $from->name === 'Jalara Support';
    });
});

it('never sends the test message to another address', function () {
    Mail::fake();

    $manager = settingsManager();
    User::factory()->create(['email' => 'someone-else@jalara.test']);

    app(SendTestMail::class)->handle($manager, app(MailSettings::class), app(BrandingSettings::class));

    Mail::assertNotSent(TestMailConfiguration::class, fn (TestMailConfiguration $mail): bool => $mail->hasTo('someone-else@jalara.test'));
});
