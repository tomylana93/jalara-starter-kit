<?php

use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Middleware\EnforceMaintenanceMode;
use App\Http\Middleware\EnforceUserAccess;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
 * `auth:sanctum` accepts both credentials this kit issues: a bearer personal
 * access token from a non-browser client, and - through the stateful middleware
 * below - the session cookie the Inertia frontend already holds. The previous
 * `web` group accepted only the latter, which left the versioned API a pattern
 * no external client could follow.
 *
 * Access enforcement is listed explicitly because it used to arrive with that
 * `web` group. Without it a token issued to an account that is later disabled,
 * suspended, or forced into a password change would keep working.
 */
Route::middleware([
    EnsureFrontendRequestsAreStateful::class,
    'auth:sanctum',
    'verified',
    EnforceUserAccess::class,
    EnforceMaintenanceMode::class,
])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('me', CurrentUserController::class)->name('me');
    });
