<?php

namespace App\Actions\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Participant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class StartConversation
{
    /**
     * Resolve the direct message between two users, creating it when it is the
     * pair's first.
     *
     * A conversation is never created on its own: this runs from the send
     * action, so an abandoned recipient search leaves nothing behind. The
     * canonical `participant_key` plus its unique index is what keeps two
     * simultaneous first messages from opening two conversations.
     *
     * Either way the participants come back with their roles, because the
     * conversation is rendered straight after the message that opened it.
     */
    public function handle(User $sender, User $recipient): Conversation
    {
        $key = Conversation::participantKeyFor($sender->id, $recipient->id);

        $existing = Conversation::query()->where('participant_key', $key)->first();

        if ($existing instanceof Conversation) {
            return $existing->load('participants.user.roles');
        }

        try {
            $conversation = DB::transaction(function () use ($key, $sender, $recipient): Conversation {
                $conversation = Conversation::query()->create(['participant_key' => $key]);

                foreach ([$sender, $recipient] as $user) {
                    Participant::query()->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                    ]);
                }

                return $conversation;
            });
        } catch (QueryException) {
            /* The other side won the race; its conversation is the canonical one. */
            $conversation = Conversation::query()->where('participant_key', $key)->firstOrFail();
        }

        return $conversation->load('participants.user.roles');
    }
}
