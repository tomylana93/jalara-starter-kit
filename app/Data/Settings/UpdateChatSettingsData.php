<?php

namespace App\Data\Settings;

final readonly class UpdateChatSettingsData
{
    public function __construct(
        public bool $chatEnabled,
        public bool $imageUploadsEnabled,
    ) {}
}
