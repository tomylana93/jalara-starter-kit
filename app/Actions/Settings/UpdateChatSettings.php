<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateChatSettingsData;
use App\Events\Chat\ChatAvailabilityChanged;
use App\Settings\ChatSettings;

final class UpdateChatSettings
{
    /**
     * Update the chat settings.
     *
     * The availability broadcast is only sent when the toggle actually moved,
     * so re-saving the same value never churns connected clients.
     */
    public function handle(ChatSettings $settings, UpdateChatSettingsData $data): ChatSettings
    {
        $changed = $settings->chatEnabled !== $data->chatEnabled
            || $settings->imageUploadsEnabled !== $data->imageUploadsEnabled;

        $settings->chatEnabled = $data->chatEnabled;
        $settings->imageUploadsEnabled = $data->imageUploadsEnabled;
        $settings->save();

        if ($changed) {
            event(new ChatAvailabilityChanged(
                $data->chatEnabled,
                $data->imageUploadsEnabled,
            ));
        }

        return $settings;
    }
}
