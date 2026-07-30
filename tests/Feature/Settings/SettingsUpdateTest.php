<?php

use App\Actions\Settings\UpdateDefaultPassword;
use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Mail\TestMailConfiguration;
use App\Models\User;
use App\Settings\AuthenticationSettings;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use App\Settings\UserProvisioningSettings;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

it('updates the general settings', function () {
    actingAs(settingsManager())
        ->put(route('settings.general.update'), [
            'applicationName' => 'Jalara',
            'description' => 'Starter kit',
            'defaultLocale' => Locale::Indonesian->value,
            'dateFormat' => DateFormat::Iso->value,
        ])
        ->assertRedirectToRoute('settings.general.edit');

    $settings = app(GeneralSettings::class)->refresh();

    expect($settings->applicationName)->toBe('Jalara')
        ->and($settings->description)->toBe('Starter kit')
        ->and($settings->defaultLocale)->toBe(Locale::Indonesian)
        ->and($settings->dateFormat)->toBe(DateFormat::Iso);
});

it('reports a general validation error on the submitted field', function () {
    actingAs(settingsManager())
        ->put(route('settings.general.update'), [
            'applicationName' => '',
            'description' => null,
            'defaultLocale' => 'fr',
            'dateFormat' => DateFormat::Iso->value,
        ])
        ->assertSessionHasErrors(['applicationName', 'defaultLocale']);
});

it('updates the authentication settings', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.authentication.update'), [
            'requireEmailVerification' => '0',
            'passwordPolicy' => PasswordPolicy::Standard->value,
            'sessionLifetimeMinutes' => 480,
        ])
        ->assertRedirectToRoute('settings.authentication.edit');

    $settings = app(AuthenticationSettings::class)->refresh();

    expect($settings->requireEmailVerification)->toBeFalse()
        ->and($settings->passwordPolicy)->toBe(PasswordPolicy::Standard)
        ->and($settings->sessionLifetimeMinutes)->toBe(480);
});

it('rejects a policy the stored default password would not satisfy', function () {
    app(UpdateDefaultPassword::class)->handle(app(UserProvisioningSettings::class), 'simplepass');

    $settings = app(AuthenticationSettings::class);
    $settings->passwordPolicy = PasswordPolicy::Basic;
    $settings->save();

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.authentication.update'), [
            'requireEmailVerification' => '1',
            'passwordPolicy' => PasswordPolicy::Strict->value,
            'sessionLifetimeMinutes' => 120,
        ])
        ->assertSessionHasErrors('passwordPolicy');

    expect(app(AuthenticationSettings::class)->refresh()->passwordPolicy)->toBe(PasswordPolicy::Basic);
});

it('updates the mail settings', function () {
    actingAs(settingsManager())
        ->put(route('settings.mail.update'), [
            'fromName' => 'Jalara Mailer',
            'fromAddress' => 'mailer@example.com',
        ])
        ->assertRedirectToRoute('settings.mail.edit');

    $settings = app(MailSettings::class)->refresh();

    expect($settings->fromName)->toBe('Jalara Mailer')
        ->and($settings->fromAddress)->toBe('mailer@example.com');
});

it('rejects an invalid sender address', function () {
    actingAs(settingsManager())
        ->put(route('settings.mail.update'), [
            'fromName' => 'Jalara Mailer',
            'fromAddress' => 'not-an-address',
        ])
        ->assertSessionHasErrors('fromAddress');
});

it('sends the test message to the signed-in manager', function () {
    Mail::fake();

    $manager = settingsManager();

    actingAs($manager)
        ->post(route('settings.mail.test'))
        ->assertRedirectToRoute('settings.mail.edit');

    Mail::assertSent(
        TestMailConfiguration::class,
        fn (TestMailConfiguration $mail) => $mail->hasTo($manager->email),
    );
});

it('updates the security settings', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.security.update'), [
            'maxFailedLoginAttempts' => 3,
            'suspensionDurationMinutes' => 30,
            'maintenanceEnabled' => '1',
        ])
        ->assertRedirectToRoute('settings.security.edit');

    $settings = app(SecuritySettings::class)->refresh();

    expect($settings->maxFailedLoginAttempts)->toBe(3)
        ->and($settings->suspensionDurationMinutes)->toBe(30)
        ->and($settings->maintenanceEnabled)->toBeTrue();
});

it('persists a disabled maintenance mode', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.security.update'), [
            'maxFailedLoginAttempts' => 5,
            'suspensionDurationMinutes' => 15,
            'maintenanceEnabled' => '0',
        ])
        ->assertRedirectToRoute('settings.security.edit');

    expect(app(SecuritySettings::class)->refresh()->maintenanceEnabled)->toBeFalse();
});

it('updates the branding settings', function () {
    actingAs(settingsManager())
        ->put(route('settings.branding.update'), [
            'companyName' => 'Jalara Group',
            'footerText' => 'All rights reserved.',
            'identityMode' => 'logo',
            'authLayout' => 'split',
            'appLayout' => 'header',
            'colorTheme' => 'emerald',
            'fontPair' => 'playfair-display-source-sans',
        ])
        ->assertRedirectToRoute('settings.branding.edit');

    $settings = app(BrandingSettings::class)->refresh();

    expect($settings->companyName)->toBe('Jalara Group')
        ->and($settings->footerText)->toBe('All rights reserved.')
        ->and($settings->identityMode->value)->toBe('logo')
        ->and($settings->authLayout->value)->toBe('split')
        ->and($settings->appLayout->value)->toBe('header')
        ->and($settings->colorTheme->value)->toBe('emerald')
        ->and($settings->fontPair->value)->toBe('playfair-display-source-sans');
});

it('rejects an unknown branding preset', function () {
    actingAs(settingsManager())
        ->put(route('settings.branding.update'), [
            'companyName' => 'Jalara Group',
            'footerText' => null,
            'identityMode' => 'icon-text',
            'authLayout' => 'simple',
            'appLayout' => 'sidebar',
            'colorTheme' => 'chartreuse',
            'fontPair' => 'poppins-inter',
        ])
        ->assertSessionHasErrors('colorTheme');
});

it('updates the default password', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.user-provisioning.update'), [
            'defaultPassword' => 'Jalara-Def4ult!',
            'defaultPassword_confirmation' => 'Jalara-Def4ult!',
        ])
        ->assertRedirectToRoute('settings.user-provisioning.edit');

    expect(app(UserProvisioningSettings::class)->refresh()->defaultPassword)->toBe('Jalara-Def4ult!');
});

it('requires a confirmed default password', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.user-provisioning.update'), [
            'defaultPassword' => 'Jalara-Def4ult!',
            'defaultPassword_confirmation' => 'Other-P4ssword!',
        ])
        ->assertSessionHasErrors('defaultPassword');

    expect(app(UserProvisioningSettings::class)->refresh()->hasDefaultPassword())->toBeFalse();
});

it('removes the default password only through the explicit request', function () {
    app(UpdateDefaultPassword::class)->handle(app(UserProvisioningSettings::class), 'Jalara-Def4ult!');

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.user-provisioning.destroy'))
        ->assertRedirectToRoute('settings.user-provisioning.edit');

    expect(app(UserProvisioningSettings::class)->refresh()->defaultPassword)->toBeNull();
});

it('forbids mutations from a user without the settings permission', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->put(route('settings.general.update'), [
            'applicationName' => 'Taken over',
            'description' => null,
            'defaultLocale' => Locale::English->value,
            'dateFormat' => DateFormat::Iso->value,
        ])
        ->assertForbidden();

    expect(app(GeneralSettings::class)->refresh()->applicationName)->not->toBe('Taken over');
});
