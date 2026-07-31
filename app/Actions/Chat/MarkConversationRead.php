<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatConversationRead;
use App\Models\Chat\Conversation;
use App\Models\Chat\Participant;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;

final class MarkConversationRead
{
    /**
     * Move the reader's marker to the newest message they actually saw.
     *
     * The marker never travels backwards, so scrolling through old history
     * cannot undo a receipt. Reading also clears the conversation's active
     * notification, which is what keeps the bell in step with the chat surface.
     */
    public function handle(Conversation $conversation, User $reader, CarbonInterface $readAt): ?Participant
    {
        $participant = $conversation->participantFor($reader);

        if (! $participant instanceof Participant) {
            return null;
        }

        if ($participant->last_read_at === null || $participant->last_read_at->lessThan($readAt)) {
            $participant->forceFill(['last_read_at' => $readAt])->save();

            event(new ChatConversationRead($participant));
        }

        $this->clearNotifications($reader, $conversation->id);

        return $participant;
    }

    private function clearNotifications(User $reader, string $conversationId): void
    {
        $reader->unreadNotifications()
            ->where('type', ChatMessageNotification::class)
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => ($notification->data['conversation_id'] ?? null) === $conversationId)
            ->each(fn (DatabaseNotification $notification) => $notification->markAsRead());
    }
}
