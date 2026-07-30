<?php

use App\Http\Controllers\Api\V1\CurrentUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('me', CurrentUserController::class)->name('me');
    });
