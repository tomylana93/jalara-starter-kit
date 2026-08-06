<?php

use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Enums\UserStatus;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Providers\SettingsServiceProvider;
use App\Settings\AuthenticationSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use App\Settings\SettingsResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function bootSettingsRuntime(): void
{
    new SettingsServiceProvider(app())->boot();
}

/**
 * Run the given assertions against a file-backed SQLite default connection.
 *
 * The probe uses its own connection name so the in-memory connection holding
 * the test transaction is never reconfigured or purged.
 */
function withSqliteDatabaseFile(string $database, Closure $assertions): void
{
    $default = config('database.default');

    config([
        'database.connections.settings_probe' => [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
        'database.default' => 'settings_probe',
    ]);

    SettingsResolver::flush();

    try {
        $assertions();
    } finally {
        config(['database.default' => $default]);
        DB::purge('settings_probe');
        SettingsResolver::flush();
    }
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

it('treats a SQLite file that has not been created yet as the pre-migration window', function () {
    withSqliteDatabaseFile(storage_path('framework/testing/not-created-yet.sqlite'), function (): void {
        expect(SettingsResolver::available())->toBeFalse()
            ->and(SettingsResolver::tryResolve(GeneralSettings::class))->toBeNull();
    });
});

it('still surfaces a genuine database failure once the SQLite file exists', function () {
    $corrupted = storage_path('framework/testing/corrupted.sqlite');
    file_put_contents($corrupted, 'this is not a SQLite database');

    try {
        withSqliteDatabaseFile($corrupted, function (): void {
            expect(fn (): bool => SettingsResolver::available())->toThrow(QueryException::class);
        });
    } finally {
        unlink($corrupted);
    }
});

it('rejects an unsupported database driver in a configured production application', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect(fn () => new AppServiceProvider(app())->boot())
        ->toThrow(LogicException::class, __('system.exception.production_database'));
});

it('tolerates the pre-install boot that has no application key yet', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.key' => null]);

    expect(fn () => new AppServiceProvider(app())->boot())->not->toThrow(LogicException::class);
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

it('blocks the application with the maintenance page while maintenance is enabled', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    /*
     * The component assertion is the point: a bare 503 body is not recognised
     * as a page by the Inertia client, which then shows it in its error modal
     * instead of replacing the screen.
     */
    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page->component('Maintenance'));
});

it('keeps API clients on a JSON body while maintenance is enabled', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(User::factory()->create())
        ->getJson(route('api.v1.me'))
        ->assertStatus(503)
        ->assertJsonPath('message', __('maintenance.message'));
});

it('keeps sign-in, sign-out, password recovery, and health checks reachable during maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    $manager = settingsManager();

    get('/up')->assertOk();
    get(route('login'))->assertOk();
    get(route('password.request'))->assertOk();
    get(route('password.reset', ['token' => 'reset-token']))->assertOk();

    post(route('password.email'), ['email' => $manager->email])->assertRedirect();

    post(route('login.store'), [
        'email' => $manager->email,
        'password' => 'password',
    ])->assertValid();

    assertAuthenticatedAs($manager);

    post(route('logout'))->assertRedirect(route('home'));

    get(route('home'))->assertRedirect(route('login'));
});

it('refuses a sign-in from an account without the settings permission during maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    $user = User::factory()->create();

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertInvalid(['email' => __('maintenance.message')]);

    assertGuest();
});

it('lets settings managers bypass maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(settingsManager())
        ->get(route('dashboard'))
        ->assertOk();
});
