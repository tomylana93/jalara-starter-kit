<?php

use App\Actions\Settings\UpdateDefaultPassword;
use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Models\User;
use App\Settings\SecuritySettings;
use App\Settings\UserProvisioningSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * @return list<string>
 */
function settingsRouteNames(): array
{
    return [
        'settings.index',
        'settings.general.edit',
        'settings.authentication.edit',
        'settings.user-provisioning.edit',
        'settings.mail.edit',
        'settings.security.edit',
        'settings.branding.edit',
    ];
}

it('redirects a guest to the login screen', function (string $route) {
    get(route($route))->assertRedirectToRoute('login');
})->with(settingsRouteNames());

it('forbids a user without the settings permission', function (string $route) {
    actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(settingsRouteNames());

it('shows every settings screen to a settings manager', function (string $route) {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertOk();
})->with(settingsRouteNames());

it('requires recent password confirmation for sensitive settings screens', function (string $route) {
    actingAs(settingsManager())
        ->get(route($route))
        ->assertRedirectToRoute('password.confirm');
})->with([
    'authentication' => 'settings.authentication.edit',
    'user provisioning' => 'settings.user-provisioning.edit',
    'security' => 'settings.security.edit',
]);

it('requires recent password confirmation for sensitive settings mutations', function (string $method, string $route) {
    actingAs(settingsManager())
        ->{$method}(route($route))
        ->assertRedirectToRoute('password.confirm');
})->with([
    'authentication' => ['put', 'settings.authentication.update'],
    'user provisioning update' => ['put', 'settings.user-provisioning.update'],
    'user provisioning deletion' => ['delete', 'settings.user-provisioning.destroy'],
    'security' => ['put', 'settings.security.update'],
]);

it('renders the settings index for a settings manager', function () {
    actingAs(settingsManager())
        ->get(route('settings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Index'),
        );
});

it('sends the general settings and localized options as scalars', function () {
    actingAs(settingsManager())
        ->get(route('settings.general.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/General')
            ->whereType('settings.applicationName', 'string')
            ->whereType('settings.defaultLocale', 'string')
            ->whereType('settings.dateFormat', 'string')
            ->has('localeOptions', count(Locale::cases()))
            ->has('dateFormatOptions', count(DateFormat::cases()))
            ->where('localeOptions.0.label', Locale::English->label())
            ->where('localeOptions.0.value', Locale::English->value),
        );
});

it('sends the localized password policy options', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.authentication.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Authentication')
            ->whereType('settings.requireEmailVerification', 'boolean')
            ->whereType('settings.sessionLifetimeMinutes', 'integer')
            ->has('passwordPolicyOptions', count(PasswordPolicy::cases()))
            ->where('passwordPolicyOptions.0.label', PasswordPolicy::Basic->label()),
        );
});

it('sends localized options in the active locale', function () {
    app()->setLocale(Locale::Indonesian->value);

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.authentication.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('passwordPolicyOptions.0.label', __('setting.password_policy.basic', locale: 'id')),
        );
});

it('never sends the default password to the client', function () {
    app(UpdateDefaultPassword::class)->handle(app(UserProvisioningSettings::class), 'Jalara-Def4ult!');

    $response = actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.user-provisioning.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/UserProvisioning')
        ->where('hasDefaultPassword', true)
        ->missing('settings')
        ->missing('defaultPassword'),
    );

    $response->assertDontSee('Jalara-Def4ult!', false);
});

it('reports a missing default password', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.user-provisioning.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('hasDefaultPassword', false));
});

it('shares whether the user may manage settings', function () {
    actingAs(settingsManager())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('can.manageSettings', true));

    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('can.manageSettings', false));
});

it('keeps the settings screens reachable during maintenance', function () {
    $settings = app(SecuritySettings::class);
    $settings->maintenanceEnabled = true;
    $settings->save();

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.security.edit'))
        ->assertOk();

    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertStatus(503);
});
