<?php

namespace App\Events\Chat;

use App\Models\Chat\Participant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Moves one participant's read receipt for the other side to render.
 */
class ChatConversationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Participant $participant) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(ChatMessageSent::channelName($this->participant->conversation_id))];
    }

    public function broadcastAs(): string
    {
        return 'chat.read';
    }

    /**
     * @return array{conversation_id: string, user_id: string, last_read_at: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->participant->conversation_id,
            'user_id' => $this->participant->user_id,
            'last_read_at' => $this->participant->last_read_at?->toIso8601String(),
        ];
    }
}
