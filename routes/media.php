<?php

use App\Http\Controllers\Media\ImageUploadController;
use Illuminate\Support\Facades\Route;

/*
 * Status surface for queued image uploads. Only `auth` is required, matching
 * the least restrictive upload it reports on (the avatar); each record is
 * additionally gated on ownership by its policy.
 */
Route::middleware(['auth'])->prefix('media')->name('media.')->group(function (): void {
    Route::get('image-uploads', [ImageUploadController::class, 'index'])->name('image-uploads.index');
    Route::get('image-uploads/{imageUpload}', [ImageUploadController::class, 'show'])->name('image-uploads.show');
    Route::delete('image-uploads/{imageUpload}', [ImageUploadController::class, 'destroy'])->name('image-uploads.destroy');
});
