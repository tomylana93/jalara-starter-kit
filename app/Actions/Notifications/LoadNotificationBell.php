<?php

namespace App\Actions\Notifications;

use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Settings\ChatSettings;
use App\Settings\SettingsResolver;
use Illuminate\Support\Collection;

final class LoadNotificationBell
{
    private const int BELL_LIMIT = 5;

    /**
     * Load the visible notifications and unread count for the bell.
     */
    public function handle(?User $user): LoadNotificationBellResult
    {
        if (! $user instanceof User) {
            return new LoadNotificationBellResult(new Collection, 0);
        }

        $chatEnabled = SettingsResolver::tryResolve(ChatSettings::class)->chatEnabled ?? false;

        $items = $user->notifications()
            ->unless($chatEnabled, ChatMessageNotification::excludeFrom(...))
            ->orderBy('id', 'desc')
            ->limit(self::BELL_LIMIT)
            ->get();

        $unreadCount = $user->unreadNotifications()
            ->unless($chatEnabled, ChatMessageNotification::excludeFrom(...))
            ->count();

        return new LoadNotificationBellResult($items, $unreadCount);
    }
}
