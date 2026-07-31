<?php

namespace App\Notifications;

use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a recipient that a direct message is waiting, without any preview.
 *
 * The body is deliberately absent from every field: the notification carries
 * the sender's name and a deep link to the chat page, nothing more. Delivery is
 * decided by {@see DeliverChatMessageNotification}, which also
 * keeps a single active notification per conversation.
 */
class ChatMessageNotification extends Notification
{
    /**
     * The semantic payload type shared by the database and broadcast channels.
     */
    public const string TYPE = 'chat.message';

    public function __construct(
        protected Conversation $conversation,
        protected User $sender,
    ) {}

    /**
     * Hide chat notifications from a notification query.
     *
     * Applied wherever notifications are listed while chat is switched off, so
     * the bell and the notification page stop surfacing a closed feature
     * without any stored row being deleted.
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public static function excludeFrom(Builder $query): void
    {
        $query->where('type', '!=', self::class);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation stored in the `data` column.
     *
     * `conversation_id` is what lets the delivery job find the conversation's
     * one active notification, and what lets a read receipt clear it.
     *
     * @return array{type: string, title: string, message: string, url: string, conversation_id: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => $this->sender->name,
            'message' => __('chat.notification.message'),
            'url' => route('chat.index', ['conversation' => $this->conversation->id]),
            'conversation_id' => $this->conversation->id,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->toDatabase($notifiable),
            'created_at' => now()->toIso8601String(),
            'read_at' => null,
        ]);
    }

    /**
     * Laravel merges this over the payload's `type`, so both channels must
     * agree on the same semantic slug.
     */
    public function broadcastType(): string
    {
        return self::TYPE;
    }
}
