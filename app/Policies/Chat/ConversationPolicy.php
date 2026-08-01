<?php

namespace App\Policies\Chat;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Only the two participants may read a conversation.
     *
     * Super Admins are deliberately excluded here: their access runs through
     * the separate audit surface, which records every opening.
     */
    public function view(User $actor, Conversation $conversation): bool
    {
        return $this->participates($actor, $conversation);
    }

    /**
     * A participant may send while the other side can still receive.
     */
    public function send(User $actor, Conversation $conversation): bool
    {
        if (! $this->participates($actor, $conversation)) {
            return false;
        }

        $counterpart = $conversation->counterpartFor($actor)?->user;

        return $counterpart instanceof User && $counterpart->status === UserStatus::Active;
    }

    /**
     * Only a Super Admin may audit conversations, and only read-only.
     */
    public function audit(User $actor): bool
    {
        return $actor->hasRole(Role::SuperAdmin->value);
    }

    public function react(User $actor, Conversation $conversation, Message $message): bool
    {
        return $this->participates($actor, $conversation)
            && $message->conversation_id === $conversation->id
            && $message->sender_id !== $actor->id;
    }

    private function participates(User $actor, Conversation $conversation): bool
    {
        return $conversation->participants->contains(
            fn ($participant): bool => $participant->user_id === $actor->id,
        );
    }
}
