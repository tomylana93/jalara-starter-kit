<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatMessageSent;
use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SendMessage
{
    /**
     * Store one immutable message and let the conversation know about it.
     *
     * The broadcast and the notification job only run once the row is
     * committed, so a client never renders a message the database rejected.
     */
    public function handle(
        Conversation $conversation,
        User $sender,
        ?string $body,
        ?UploadedFile $image = null,
    ): Message {
        $imagePath = null;
        $imageMimeType = null;

        if ($image instanceof UploadedFile) {
            $imageMimeType = $image->getMimeType();
            $imagePath = $image->storeAs(
                'chat/'.$conversation->id,
                Str::uuid().'.'.$image->extension(),
                'local',
            );

            throw_unless(is_string($imagePath), RuntimeException::class, 'The chat image could not be stored.');
        }

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

        event(new ChatMessageSent($message));
        dispatch(new DeliverChatMessageNotification($message));

        return $message;
    }
}
