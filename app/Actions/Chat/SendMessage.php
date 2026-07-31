<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatMessageSent;
use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SendMessage
{
    /**
     * Store one immutable message and let the conversation know about it.
     *
     * The broadcast and the notification job only run once the row is
     * committed, so a client never renders a message the database rejected.
     */
    public function handle(Conversation $conversation, User $sender, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body): Message {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $body,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            /*
             * The sender has by definition seen what they just wrote, so their
             * own marker moves with the message and the conversation never
             * shows up unread on their side.
             */
            Participant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => $message->created_at]);

            return $message;
        });

        $message->setRelation('sender', $sender);

        event(new ChatMessageSent($message));
        dispatch(new DeliverChatMessageNotification($message));

        return $message;
    }
}
