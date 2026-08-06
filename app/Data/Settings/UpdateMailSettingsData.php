<?php

namespace App\Data\Settings;

final readonly class UpdateMailSettingsData
{
    public function __construct(
        public string $fromName,
        public string $fromAddress,
    ) {}
}
