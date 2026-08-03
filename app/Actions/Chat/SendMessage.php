<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatMessageSent;
use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SendMessage
{
    /**
     * Store one immutable message and let the conversation know about it.
     *
     * The broadcast and the notification job only run once the row is
     * committed, so a client never renders a message the database rejected.
     *
     * An image arrives already processed and stored on the private disk — the
     * queue owns that step — and is removed again if this transaction fails, so
     * a rejected message never leaves a file behind.
     *
     * "Committed" is meant literally. When the queue calls this it does so
     * inside its own transaction, and a broadcast sent from in there would
     * reach every client seconds before a rollback erased the message they were
     * shown. Deferring both side effects to commit also makes a retried job
     * harmless: the attempt that rolled back never announced anything, so the
     * recipient sees exactly one message and gets exactly one notification.
     */
    public function handle(
        Conversation $conversation,
        User $sender,
        ?string $body,
        ?string $imagePath = null,
        ?string $imageMimeType = null,
    ): Message {
        try {
            $message = DB::transaction(function () use ($conversation, $sender, $body, $imagePath, $imageMimeType): Message {
                $message = new Message;
                $message->forceFill([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                    'image_path' => $imagePath,
                    'image_mime_type' => $imageMimeType,
                ]);
                $message->save();

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
        } catch (Throwable $throwable) {
            if ($imagePath !== null) {
                Storage::disk('local')->delete($imagePath);
            }

            throw $throwable;
        }

        $message->setRelation('sender', $sender);
        $message->setRelation('reactions', collect());

        DB::afterCommit(function () use ($message): void {
            event(new ChatMessageSent($message));
            dispatch(new DeliverChatMessageNotification($message));
        });

        return $message;
    }
}
