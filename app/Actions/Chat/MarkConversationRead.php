<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatConversationRead;
use App\Models\Chat\Conversation;
use App\Models\Chat\Participant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;

final readonly class MarkConversationRead
{
    public function __construct(
        private LoadUnreadConversationNotifications $unreadConversationNotifications,
    ) {}

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

        /* Marked read, never deleted: the row stays as the reader's history. */
        $this->unreadConversationNotifications->handle($reader, $conversation->id)
            ->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return $participant;
    }
}
