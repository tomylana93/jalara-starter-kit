<?php

namespace App\Actions\Chat;

use App\Models\User;
use App\Notifications\ChatMessageNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Selects the user's unread chat notifications for one conversation.
 *
 * Both chat workflows that touch these records share this selection and then
 * differ deliberately: delivery deletes the matches before sending their
 * replacement, while reading marks them read and keeps the rows.
 */
final class LoadUnreadConversationNotifications
{
    /**
     * The unread set is small, so it is filtered in PHP rather than through a
     * JSON path expression, which the `data` text column does not index anyway.
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function handle(User $user, string $conversationId): Collection
    {
        return $user->unreadNotifications()
            ->where('type', ChatMessageNotification::class)
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => ($notification->data['conversation_id'] ?? null) === $conversationId);
    }
}
