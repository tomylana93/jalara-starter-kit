<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

/*
 * These render only outside the local environment, so the Ignition modal stays
 * available while developing. The test environment is not local, so the pages
 * are exercised here exactly as production serves them.
 */
it('renders a missing page as the error page', function () {
    get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404),
        );
});

it('renders a forbidden page as the error page', function () {
    actingAs(User::factory()->create())
        ->get(route('settings.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 403),
        );
});

it('keeps API clients on a JSON body for a missing endpoint', function () {
    getJson('/api/v1/no-such-endpoint')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
