<?php

use App\Enums\UserStatus;
use App\Events\Chat\ChatAvailabilityChanged;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return $user->id === $id;
});

/*
 * The global chat toggle. Every Active user listens here so switching chat off
 * closes their surface immediately; the payload carries the flag and nothing
 * else.
 */
Broadcast::channel(ChatAvailabilityChanged::CHANNEL, function (User $user): bool {
    return $user->status === UserStatus::Active;
});

/*
 * One conversation's realtime traffic. Only its two participants are
 * authorized, so message bodies and read receipts never reach anyone else -
 * including a Super Admin, whose access runs through the audited surface.
 */
Broadcast::channel('chat.conversation.{conversation}', function (User $user, string $conversation): bool {
    return Conversation::query()
        ->whereKey($conversation)
        ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
        ->exists();
});
