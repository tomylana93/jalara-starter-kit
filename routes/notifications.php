<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');

        /* Declared first so the literal segment is not read as a {notification}. */
        Route::patch('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');

        Route::patch('{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
    });
