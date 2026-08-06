<?php

namespace App\Actions\Chat;

use App\Enums\UserStatus;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Notifications\ChatMessageNotification;
use App\Settings\ChatSettings;
use App\Support\Chat\TrackChatPageContext;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Creates the recipient's chat notification, if their context still calls for
 * one.
 *
 * This runs a moment after the message was stored and broadcast, and two things
 * can silence it:
 *
 * - The dedicated Chat page is open, which shows every conversation, so no
 *   direct message needs announcing while the recipient is on it.
 * - The recipient's client was already looking at that conversation and has
 *   moved its read marker past the message. That is how an expanded widget
 *   stays silent for the direct message it shows, while a minimized widget or
 *   any other page leaves the marker behind and the notification is created.
 *
 * Neither is presence: nothing is broadcast and no other user can observe them.
 *
 * Exactly one notification stays active per conversation: an existing unread
 * one is replaced so the bell shows the latest message rather than a stack.
 */
final readonly class NotifyChatMessageRecipient
{
    public function __construct(
        private ChatSettings $settings,
        private TrackChatPageContext $chatPageContext,
        private LoadUnreadConversationNotifications $unreadConversationNotifications,
    ) {}

    public function handle(Message $message): void
    {
        if (! $this->settings->chatEnabled) {
            return;
        }

        $conversation = $message->conversation()->with('participants.user')->first();

        if ($conversation === null) {
            return;
        }

        $recipient = $conversation->participants
            ->first(fn (Participant $participant): bool => $participant->user_id !== $message->sender_id);

        if ($recipient === null) {
            return;
        }

        /* A recipient who can no longer sign in receives no new notification. */
        if ($recipient->user->status !== UserStatus::Active) {
            return;
        }

        /* The Chat page is open, and it already shows every conversation. */
        if ($this->chatPageContext->isOpen($recipient->user)) {
            return;
        }

        /* Already read: the recipient was looking at this conversation. */
        if ($recipient->last_read_at !== null
            && $message->created_at !== null
            && $recipient->last_read_at->greaterThanOrEqualTo($message->created_at)) {
            return;
        }

        /* Replaced, not stacked: the superseded row is dropped, never kept as history. */
        $this->unreadConversationNotifications->handle($recipient->user, $conversation->id)
            ->each(fn (DatabaseNotification $notification) => $notification->delete());

        $recipient->user->notify(new ChatMessageNotification($conversation, $message->sender));
    }
}
