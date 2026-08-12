<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Enums\Role;

/*
 * One sweep across every main page, proving each one boots in a real browser
 * and renders the content it is supposed to.
 *
 * The positive per-page assertion is the point. `assertNoSmoke()` alone only
 * proves the absence of console logs and JavaScript errors, and a page that
 * renders nothing at all produces neither — so a blank page passes it. Pairing
 * the sweep with one specific expected string per page is what turns it into
 * evidence that the bundle actually ran. Both assertions share a single
 * `visit()`, so the browser context is paid for once.
 */

/**
 * @return array<string, string> path => text only a rendered page shows
 */
function smokePages(): array
{
    return [
        '/' => 'Dashboard',
        '/account/profile' => 'Profile',
        '/account/security' => 'Password',
        '/account/api-tokens' => 'API',
        '/chat' => 'Chat',
        '/chat/audit' => 'Audit',
        '/documentation' => 'Documentation',
        '/documentation/manage' => 'Documentation',
        '/documentation/manage/create' => 'Title',
        '/master-data/users' => 'Users',
        '/master-data/users/create' => 'Name',
        '/notifications' => 'Notifications',
        '/settings/authentication' => 'Authentication',
        '/settings/backups' => 'Backups',
        '/settings/branding' => 'Branding',
        '/settings/chat' => 'Chat',
        '/settings/general' => 'General',
        '/settings/mail' => 'Mail',
        '/settings/security' => 'Security',
        '/settings/user-provisioning' => 'password',
    ];
}

it('renders every main page in a real browser', function () {
    app(SyncAuthorization::class)->handle();

    $this->actingAs(userWithRole(Role::SuperAdmin));

    /*
     * Several settings screens sit behind RequirePassword. The sweep is about
     * whether a page renders, not about the confirmation barrier — that is a
     * Feature-test concern — so the window is opened up front.
     */
    session(['auth.password_confirmed_at' => time()]);

    $pages = visit(array_keys(smokePages()));

    $pages->assertNoSmoke();

    foreach (array_values(smokePages()) as $index => $expected) {
        $pages[$index]->assertSee($expected);
    }
});
