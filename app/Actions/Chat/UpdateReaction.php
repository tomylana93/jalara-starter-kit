<?php

namespace App\Actions\Chat;

use App\Events\Chat\ChatReactionChanged;
use App\Models\Chat\Message;
use App\Models\Chat\Reaction;
use App\Models\User;

class UpdateReaction
{
    public function handle(Message $message, User $user, ?string $emoji): ?Reaction
    {
        $reaction = $message->reactions()->where('user_id', $user->id)->first();

        if ($emoji === null) {
            $reaction?->delete();
            $reaction = null;
        } elseif ($reaction instanceof Reaction) {
            $reaction->forceFill(['emoji' => $emoji])->save();
        } else {
            $reaction = new Reaction;
            $reaction->forceFill([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
            $reaction->save();
        }

        event(new ChatReactionChanged($message, $reaction, $user->id));

        return $reaction;
    }
}
