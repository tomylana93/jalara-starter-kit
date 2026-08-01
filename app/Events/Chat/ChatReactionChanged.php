<?php

namespace App\Events\Chat;

use App\Http\Presenters\ChatPresenter;
use App\Models\Chat\Message;
use App\Models\Chat\Reaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatReactionChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public ?Reaction $reaction,
        public string $userId,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(ChatMessageSent::channelName($this->message->conversation_id))];
    }

    public function broadcastAs(): string
    {
        return 'chat.reaction';
    }

    /** @return array{message_id: string, user_id: string, reaction: array{id: string, user_id: string, emoji: string}|null} */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'user_id' => $this->userId,
            'reaction' => ChatPresenter::reaction($this->reaction),
        ];
    }
}
