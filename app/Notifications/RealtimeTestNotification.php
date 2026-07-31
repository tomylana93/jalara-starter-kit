<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Reference notification for the database + broadcast payload contract.
 *
 * Every application notification should keep this payload shape so a single
 * client-side type can render both the persisted history and the realtime
 * broadcast. `type` is a stable semantic slug, never a PHP class name, and
 * `url` is an optional in-app destination that is rendered as text, never HTML.
 */
class RealtimeTestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The semantic payload type shared by the database and broadcast channels.
     */
    public const string TYPE = 'test';

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $url = null,
    ) {}

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
     * Timestamps and read state are omitted here because the notifications
     * table owns them as real columns.
     *
     * @return array{type: string, title: string, message: string, url: string|null}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * `created_at` and `read_at` are inlined here because a broadcast carries
     * no database row for the client to read them from.
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
     * Get the type of the notification being broadcast.
     *
     * Laravel merges this over the payload's `type` key, so it must return the
     * same semantic slug to keep both channels on a single contract.
     */
    public function broadcastType(): string
    {
        return self::TYPE;
    }
}
