<?php

use App\Enums\Role;
use App\Http\Controllers\Documentation\DocumentationCategoryController;
use App\Http\Controllers\Documentation\DocumentationController;
use App\Http\Controllers\Documentation\DocumentationManagementController;
use App\Http\Controllers\Documentation\DocumentationSearchController;
use App\Http\Controllers\Documentation\DocumentationWriteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('documentation')->name('documentation.')->group(function (): void {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('search', DocumentationSearchController::class)->name('search');
    Route::middleware('role:'.Role::SuperAdmin->value)->prefix('manage')->name('manage.')->group(function (): void {
        Route::get('/', [DocumentationManagementController::class, 'index'])->name('index');
        Route::get('create', [DocumentationWriteController::class, 'create'])->name('create');
        Route::post('documents', [DocumentationWriteController::class, 'store'])->name('documents.store');
        Route::get('documents/{documentation}/edit', [DocumentationWriteController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{documentation}', [DocumentationWriteController::class, 'update'])->name('documents.update');
        Route::delete('documents/{documentation}', [DocumentationWriteController::class, 'destroy'])->name('documents.destroy');
        Route::post('documents/{documentation}/move/{direction}', [DocumentationWriteController::class, 'move'])->name('documents.move');
        Route::post('categories', [DocumentationCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{documentationCategory}', [DocumentationCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{documentationCategory}', [DocumentationCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('categories/{documentationCategory}/move/{direction}', [DocumentationCategoryController::class, 'move'])->name('categories.move');
    });

    Route::get('{documentation}', [DocumentationController::class, 'show'])->name('show');
});
