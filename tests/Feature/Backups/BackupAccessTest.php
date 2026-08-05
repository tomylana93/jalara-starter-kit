<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects a guest to the login screen', function () {
    get(route('settings.backups.index'))->assertRedirectToRoute('login');
});

it('forbids a user without the backup permission', function () {
    actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.index'))
        ->assertForbidden();
});

/*
 * The settings permission must not reach this surface. If it ever does, the
 * separation the feature was built around has quietly collapsed.
 */
it('forbids a settings manager who does not manage backups', function () {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.index'))
        ->assertForbidden();
});

it('forbids a user without the backup permission on every write route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.store'))
        ->assertForbidden();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.download', ['filename' => 'anything.zip']))
        ->assertForbidden();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.backups.destroy', ['filename' => 'anything.zip']))
        ->assertForbidden();
});

it('requires a recent password confirmation', function () {
    actingAs(backupManager())
        ->get(route('settings.backups.index'))
        ->assertRedirectToRoute('password.confirm');
});

/*
 * The settings index is a hub reachable by either ability, so a holder of only
 * the backup permission still has a way in. Without this the permission would be
 * grantable but unreachable.
 */
it('lets a backup manager reach the settings index', function () {
    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.index'))
        ->assertOk();
});

it('still refuses a backup manager every settings page', function (string $route) {
    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertForbidden();
})->with([
    'settings.general.edit',
    'settings.authentication.edit',
    'settings.mail.edit',
    'settings.branding.edit',
]);
