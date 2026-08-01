<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * The authenticated shell renders the sidebar, whose viewport and
 * colour-scheme media queries cannot be evaluated on the server. Those pages
 * are excluded from SSR so hydration never has to reconcile a server render
 * that guessed the wrong branch.
 */
beforeEach(function (): void {
    config([
        'inertia.ssr.enabled' => true,
        'inertia.ssr.ensure_bundle_exists' => false,
    ]);

    Http::fake();
});

/*
 * Inertia posts to the Vite dev server's "/__inertia_ssr" endpoint instead of
 * the bundle's "/render" whenever a hot marker exists, and the stubbed Vite of
 * the base test case still reads the real marker. Both endpoints prove the page
 * was rendered on the server, so the assertion accepts either rather than
 * depending on whether a development session happens to be running.
 */
it('server side renders guest pages', function () {
    get(route('login'))->assertOk();

    Http::assertSent(fn (Request $request) => in_array(
        parse_url($request->url(), PHP_URL_PATH),
        ['/render', '/__inertia_ssr'],
        true,
    ));
});

it('skips server side rendering for pages that render the application shell', function (string $route) {
    actingAs(settingsManager());

    get($route)->assertOk();

    Http::assertNothingSent();
})->with([
    'dashboard' => fn () => route('dashboard'),
    'account' => fn () => route('account.profile.edit'),
    'settings' => fn () => route('settings.branding.edit'),
]);
