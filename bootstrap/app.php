<?php

use App\Http\Middleware\EnforceMaintenanceMode;
use App\Http\Middleware\EnforceUserAccess;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => EnsureEmailIsVerifiedWhenRequired::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            EnforceUserAccess::class,
            EnforceMaintenanceMode::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

/*
 * `Application::storagePath()` reads LARAVEL_STORAGE_PATH from `$_ENV` and
 * `$_SERVER` only. With `variables_order=GPCS` the `php -S` child that
 * `artisan serve` spawns exposes the inherited value through `getenv()` alone,
 * so served requests would silently fall back to the default storage path while
 * CLI processes honored the override. Bridging it here keeps every process on
 * one root. Absent or empty, the default behavior is untouched.
 */
$storagePath = getenv('LARAVEL_STORAGE_PATH');

if (is_string($storagePath) && $storagePath !== '') {
    $application->useStoragePath($storagePath);
}

return $application;
