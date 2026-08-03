<?php

namespace App\Actions\Chat;

use App\Models\Chat\Conversation;
use App\Models\User;
use App\Settings\ChatSettings;
use App\Settings\SettingsResolver;

final class LoadSharedChatState
{
    /**
     * Resolve the shared chat layout state.
     *
     * @return array{enabled: bool, imageUploadsEnabled: bool, unreadCount: int}
     */
    public function handle(?User $user): array
    {
        $chatSettings = SettingsResolver::tryResolve(ChatSettings::class);
        $enabled = $chatSettings->chatEnabled ?? false;

        if (! $enabled || ! $user instanceof User) {
            return [
                'enabled' => false,
                'imageUploadsEnabled' => false,
                'unreadCount' => 0,
            ];
        }

        return [
            'enabled' => true,
            'imageUploadsEnabled' => $chatSettings->imageUploadsEnabled ?? false,
            'unreadCount' => Conversation::unreadMessageCountFor($user),
        ];
    }
}
