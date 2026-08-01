<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ChatSettings extends Settings
{
    /**
     * Whether the direct message surface is available to users at all.
     *
     * Turning it off closes every user-facing chat surface immediately and
     * never removes stored conversations, messages, or audit records.
     */
    public bool $chatEnabled;

    /** Whether users may attach a new image to a chat message. */
    public bool $imageUploadsEnabled;

    public static function group(): string
    {
        return 'chat';
    }
}
