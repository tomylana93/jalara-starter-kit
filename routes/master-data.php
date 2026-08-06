<?php

use App\Enums\Permission;
use App\Http\Controllers\MasterData\ExportUsersController;
use App\Http\Controllers\MasterData\ExportUsersPdfController;
use App\Http\Controllers\MasterData\ImportUsersController;
use App\Http\Controllers\MasterData\UserController;
use App\Http\Controllers\MasterData\UserImportTemplateController;
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

        /* Declared first so the literal segments are not read as a {user}. */
        /* Named by format rather than leaving one of them looking like a
           default: both are equals, and a reader should not have to guess. */
        Route::get('users/export/excel', ExportUsersController::class)->name('users.export.excel');
        Route::get('users/export/pdf', ExportUsersPdfController::class)->name('users.export.pdf');
        Route::get('users/import/template', UserImportTemplateController::class)->name('users.import.template');
        Route::post('users/import', ImportUsersController::class)->name('users.import');

        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
    });
