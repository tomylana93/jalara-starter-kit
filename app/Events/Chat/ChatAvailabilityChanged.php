<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Announces that the global chat toggle changed.
 *
 * Online clients act on this immediately: the surface closes when chat is
 * switched off and comes back when it is switched on. No stored data changes.
 */
class ChatAvailabilityChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The channel every Active user listens on for the global chat toggle.
     */
    public const string CHANNEL = 'chat.control';

    public function __construct(public bool $enabled) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::CHANNEL)];
    }

    public function broadcastAs(): string
    {
        return 'chat.availability';
    }

    /**
     * @return array{enabled: bool}
     */
    public function broadcastWith(): array
    {
        return ['enabled' => $this->enabled];
    }
}
