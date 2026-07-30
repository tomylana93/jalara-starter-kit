<?php

use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Enums\UserStatus;
use App\Models\User;
use App\Providers\SettingsServiceProvider;
use App\Settings\AuthenticationSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use App\Settings\SettingsResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function bootSettingsRuntime(): void
{
    new SettingsServiceProvider(app())->boot();
}

it('applies the application name, locale, and session lifetime at boot', function () {
    $general = app(GeneralSettings::class);
    $general->applicationName = 'Jalara';
    $general->defaultLocale = Locale::Indonesian;
    $general->dateFormat = DateFormat::Iso;
    $general->save();

    $authentication = app(AuthenticationSettings::class);
    $authentication->sessionLifetimeMinutes = 45;
    $authentication->save();

    bootSettingsRuntime();

    expect(config('app.name'))->toBe('Jalara')
        ->and(config('app.locale'))->toBe('id')
        ->and(app()->getLocale())->toBe('id')
        ->and(config('session.lifetime'))->toBe(45);
});

it('applies the mail sender identity at boot', function () {
    $mail = app(MailSettings::class);
    $mail->fromName = 'Jalara Support';
    $mail->fromAddress = 'support@jalara.test';
    $mail->save();

    bootSettingsRuntime();

    expect(config('mail.from.name'))->toBe('Jalara Support')
        ->and(config('mail.from.address'))->toBe('support@jalara.test');
});

it('falls back to configuration when the settings table is missing', function () {
    config(['app.name' => 'Fallback App', 'session.lifetime' => 99]);

    Schema::drop('settings_properties');
    SettingsResolver::flush();
    app()->forgetInstance(GeneralSettings::class);
    app()->forgetInstance(AuthenticationSettings::class);
    app()->forgetInstance(MailSettings::class);

    bootSettingsRuntime();

    expect(SettingsResolver::available())->toBeFalse()
        ->and(config('app.name'))->toBe('Fallback App')
        ->and(config('session.lifetime'))->toBe(99)
        ->and(Password::defaults()->toPasswordRulesString())
        ->toBe(PasswordPolicy::Strict->rule()->toPasswordRulesString());
});

it('resolves the password rules from the active policy preset', function (PasswordPolicy $policy) {
    $settings = app(AuthenticationSettings::class);
    $settings->passwordPolicy = $policy;
    $settings->save();

    expect(Password::defaults()->toPasswordRulesString())
        ->toBe($policy->rule()->toPasswordRulesString());
})->with([
    'basic' => PasswordPolicy::Basic,
    'standard' => PasswordPolicy::Standard,
    'strict' => PasswordPolicy::Strict,
]);

it('rejects passwords that do not satisfy the active policy', function () {
    $settings = app(AuthenticationSettings::class);
    $settings->passwordPolicy = PasswordPolicy::Standard;
    $settings->save();

    $user = User::factory()->create();

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'Jalara12345',
            'password_confirmation' => 'Jalara12345',
        ])
        ->assertSessionHasNoErrors();
});

it('requires email verification only while the setting is enabled', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

    $settings = app(AuthenticationSettings::class);
    $settings->requireEmailVerification = false;
    $settings->save();

    actingAs($user)->get(route('dashboard'))->assertOk();

    $settings->requireEmailVerification = true;
    $settings->save();

    actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

it('applies the configured login throttle without changing account status', function () {
    $settings = app(SecuritySettings::class);
    $settings->maxFailedLoginAttempts = 2;
    $settings->suspensionDurationMinutes = 90;
    $settings->save();

    $user = User::factory()->create();

    foreach (range(1, 2) as $ignored) {
        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();

    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->failed_login_attempts)->toBe(0)
        ->and($user->suspended_until)->toBeNull();
});

it('blocks the application while maintenance is enabled', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertStatus(503);
});

it('keeps login, logout, and health checks reachable during maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    get(route('login'))->assertOk();
    get('/up')->assertOk();

    actingAs(User::factory()->create())->post(route('logout'))->assertRedirect();
});

it('lets settings managers bypass maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(settingsManager())
        ->get(route('dashboard'))
        ->assertOk();
});
