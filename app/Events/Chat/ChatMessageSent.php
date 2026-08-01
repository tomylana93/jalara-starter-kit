<?php

namespace App\Events\Chat;

use App\Http\Presenters\ChatPresenter;
use App\Models\Chat\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Carries a stored message to the two participants of its conversation.
 *
 * The channel is private and authorized per participant, so nobody else ever
 * receives the body. Only the presenter's explicit fields are broadcast.
 */
class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * The private channel carrying one conversation's realtime traffic.
     */
    public static function channelName(string $conversationId): string
    {
        return 'chat.conversation.'.$conversationId;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::channelName($this->message->conversation_id))];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array{message: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        return ['message' => ChatPresenter::message($this->message)];
    }
}
