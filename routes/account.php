<?php

declare(strict_types=1);

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->group(function (): void {
    Route::middleware(['auth'])->group(function (): void {

        Route::redirect('/', '/account/profile');

        Route::get('profile', [ProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])
            ->name('profile.update');
    });

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::delete('profile', [ProfileController::class, 'destroy'])
            ->name('profile.destroy');

        Route::get('security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('password.update');
    });

    Route::get('.well-known/passkey-endpoints', fn () => response()->json([
        'enroll' => route('account.security.edit'),
        'manage' => route('account.security.edit'),
    ]))->name('well-known.passkeys');
});
