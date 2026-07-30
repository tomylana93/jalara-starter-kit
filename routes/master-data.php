<?php

use App\Enums\Permission;
use App\Http\Controllers\MasterData\ExportUsersController;
use App\Http\Controllers\MasterData\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

/*
 * Master Data has no delete surface: an account's lifecycle is expressed by its
 * UserStatus, so the resource deliberately omits destroy (and show, which the
 * table already covers).
 */
Route::middleware(['auth', 'verified', 'permission:'.Permission::ViewUsers->value])
    ->prefix('master-data')
    ->name('master-data.')
    ->group(function (): void {
        Route::inertia('/', 'master-data/Index')->name('index');

        /* Declared first so the literal segment is not read as a {user}. */
        Route::get('users/export', ExportUsersController::class)->name('users.export');

        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
    });
