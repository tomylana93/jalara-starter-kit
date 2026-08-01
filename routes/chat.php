<?php

use App\Enums\Role;
use App\Http\Controllers\Chat\AuditController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\ChatPageContextController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\MessageImageController;
use App\Http\Controllers\Chat\ReactionController;
use App\Http\Controllers\Chat\RecipientController;
use App\Http\Middleware\EnsureChatIsEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('chat')
    ->name('chat.')
    ->group(function (): void {
        /*
         * Audit is declared first so its literal segment is not read as a
         * {conversation}, and it stays outside the feature toggle: switching
         * chat off closes the user surface, it does not remove the history a
         * Super Admin is accountable for.
         */
        Route::middleware('role:'.Role::SuperAdmin->value)
            ->prefix('audit')
            ->name('audit.')
            ->group(function (): void {
                Route::get('/', [AuditController::class, 'index'])->name('index');
                Route::get('messages/{message}/image', [MessageImageController::class, 'audit'])
                    ->name('messages.image');
                Route::get('{conversation}', [AuditController::class, 'show'])->name('show');
            });

        Route::middleware(EnsureChatIsEnabled::class)->group(function (): void {
            Route::get('/', [ChatController::class, 'index'])->name('index');

            /* Standalone JSON endpoints backing the page and the desktop widget. */
            Route::get('recipients', [RecipientController::class, 'index'])->name('recipients.index');

            /*
             * The Chat page reports that it is open so its own notifications
             * stay silent while it is. Private to the reporting user.
             */
            Route::post('context', [ChatPageContextController::class, 'store'])->name('context.store');
            Route::delete('context', [ChatPageContextController::class, 'destroy'])->name('context.destroy');

            Route::post('messages', [MessageController::class, 'store'])
                ->middleware('throttle:chat-messages')
                ->name('messages.store');
            Route::get('messages/{message}/image', [MessageImageController::class, 'show'])
                ->name('messages.image');
            Route::put('messages/{message}/reaction', [ReactionController::class, 'update'])
                ->name('messages.reaction.update');
            Route::delete('messages/{message}/reaction', [ReactionController::class, 'destroy'])
                ->name('messages.reaction.destroy');

            Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
            Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
            Route::post('conversations/{conversation}/read', [ConversationController::class, 'read'])->name('conversations.read');
        });
    });
