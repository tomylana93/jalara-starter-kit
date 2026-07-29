<?php

use App\Enums\Permission;
use App\Http\Controllers\Settings\AuthenticationSettingsController;
use App\Http\Controllers\Settings\BrandingSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\MailSettingsController;
use App\Http\Controllers\Settings\SecuritySettingsController;
use App\Http\Controllers\Settings\UserProvisioningSettingsController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:'.Permission::ManageSettings->value])
    ->prefix('settings')
    ->name('settings.')
    ->group(function (): void {
        Route::inertia('/', 'settings/Index')->name('index');

        Route::get('general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
        Route::put('general', [GeneralSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('general.update');

        Route::get('authentication', [AuthenticationSettingsController::class, 'edit'])->name('authentication.edit');
        Route::put('authentication', [AuthenticationSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('authentication.update');

        Route::get('user-provisioning', [UserProvisioningSettingsController::class, 'edit'])->name('user-provisioning.edit');
        Route::put('user-provisioning/default-password', [UserProvisioningSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('user-provisioning.update');
        Route::delete('user-provisioning/default-password', [UserProvisioningSettingsController::class, 'destroy'])->name('user-provisioning.destroy');

        Route::get('mail', [MailSettingsController::class, 'edit'])->name('mail.edit');
        Route::put('mail', [MailSettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('mail.update');
        Route::post('mail/test', [MailSettingsController::class, 'test'])->name('mail.test');

        Route::get('security', [SecuritySettingsController::class, 'edit'])->name('security.edit');
        Route::put('security', [SecuritySettingsController::class, 'update'])
            ->middleware(HandlePrecognitiveRequests::class)
            ->name('security.update');

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
