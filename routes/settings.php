<?php

use App\Enums\Permission;
use App\Http\Controllers\Backups\BackupController;
use App\Http\Controllers\Settings\AuthenticationSettingsController;
use App\Http\Controllers\Settings\BrandingSettingsController;
use App\Http\Controllers\Settings\ChatSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\MailSettingsController;
use App\Http\Controllers\Settings\SecuritySettingsController;
use App\Http\Controllers\Settings\UserProvisioningSettingsController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

/*
 * The index is a hub, so it answers to either ability that owns a card on it.
 * Every section below still enforces its own permission: reaching the hub grants
 * nothing except the list of places the holder may actually go.
 */
Route::middleware([
    'auth',
    'verified',
    'permission:'.Permission::ManageSettings->value.'|'.Permission::ManageBackups->value,
])
    ->prefix('settings')
    ->name('settings.')
    ->group(function (): void {
        Route::inertia('/', 'settings/Index')->name('index');
    });

/*
 * Backups sit under Settings by placement only. Authorization stays separate
 * from `manage settings`, because this surface hands out a full copy of the
 * database and must be grantable - and revocable - on its own.
 *
 * The whole group sits behind password confirmation. Note what that does and
 * does not buy: Laravel's confirmation window is three hours, so this is a
 * barrier at the start of a session, not a check on each download.
 */
Route::middleware([
    'auth',
    'verified',
    'permission:'.Permission::ManageBackups->value,
    RequirePassword::class,
])
    ->prefix('settings/backups')
    ->name('settings.backups.')
    ->group(function (): void {
        Route::get('/', [BackupController::class, 'index'])->name('index');

        Route::post('/', [BackupController::class, 'store'])->name('store');

        /*
         * The filename is a route parameter but never a path: the controller
         * resolves it against the real archive listing. Laravel's default
         * parameter pattern already refuses slashes, which is a second, weaker
         * line of defence rather than the primary one.
         */
        Route::get('{filename}/download', [BackupController::class, 'download'])->name('download');
        Route::delete('{filename}', [BackupController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'verified', 'permission:'.Permission::ManageSettings->value])
    ->prefix('settings')
    ->name('settings.')
    ->group(function (): void {
        Route::get('general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
        Route::put('general', [GeneralSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('general.update');

        Route::middleware(RequirePassword::class)->group(function (): void {
            Route::get('authentication', [AuthenticationSettingsController::class, 'edit'])->name('authentication.edit');
            Route::put('authentication', [AuthenticationSettingsController::class, 'update'])
                ->middleware(HandlePrecognitiveRequests::class)
                ->name('authentication.update');

            Route::get('user-provisioning', [UserProvisioningSettingsController::class, 'edit'])->name('user-provisioning.edit');
            Route::put('user-provisioning/default-password', [UserProvisioningSettingsController::class, 'update'])
                ->middleware(HandlePrecognitiveRequests::class)
                ->name('user-provisioning.update');
            Route::delete('user-provisioning/default-password', [UserProvisioningSettingsController::class, 'destroy'])->name('user-provisioning.destroy');

            Route::get('security', [SecuritySettingsController::class, 'edit'])->name('security.edit');
            Route::put('security', [SecuritySettingsController::class, 'update'])
                ->middleware(HandlePrecognitiveRequests::class)
                ->name('security.update');

            /*
             * Reachable even while chat is switched off: the toggle is what
             * brings the surface back.
             */
            Route::get('chat', [ChatSettingsController::class, 'edit'])->name('chat.edit');
            Route::put('chat', [ChatSettingsController::class, 'update'])
                ->middleware(HandlePrecognitiveRequests::class)
                ->name('chat.update');
        });

        Route::get('mail', [MailSettingsController::class, 'edit'])->name('mail.edit');
        Route::put('mail', [MailSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('mail.update');
        Route::post('mail/test', [MailSettingsController::class, 'test'])->name('mail.test');

        Route::get('branding', [BrandingSettingsController::class, 'edit'])->name('branding.edit');
        Route::put('branding', [BrandingSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('branding.update');

        /*
         * Images use dedicated POST/DELETE endpoints: a multipart body cannot
         * travel reliably through the spoofed PUT the settings form uses.
         */
        Route::post('branding/assets/{asset}', [BrandingSettingsController::class, 'storeAsset'])->name('branding.asset.store');
        Route::delete('branding/assets/{asset}', [BrandingSettingsController::class, 'destroyAsset'])->name('branding.asset.destroy');
    });
