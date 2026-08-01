<?php

namespace App\Actions\Settings;

use App\Events\Chat\ChatAvailabilityChanged;
use App\Settings\ChatSettings;

final class UpdateChatSettings
{
    /**
     * Update the chat settings.
     *
     * The availability broadcast is only sent when the toggle actually moved,
     * so re-saving the same value never churns connected clients.
     *
     * @param  array{chatEnabled: bool, imageUploadsEnabled: bool}  $attributes
     */
    public function handle(ChatSettings $settings, array $attributes): ChatSettings
    {
        $changed = $settings->chatEnabled !== $attributes['chatEnabled']
            || $settings->imageUploadsEnabled !== $attributes['imageUploadsEnabled'];

        $settings->chatEnabled = $attributes['chatEnabled'];
        $settings->imageUploadsEnabled = $attributes['imageUploadsEnabled'];
        $settings->save();

        if ($changed) {
            event(new ChatAvailabilityChanged(
                $attributes['chatEnabled'],
                $attributes['imageUploadsEnabled'],
            ));
        }

        return $settings;
    }
}
