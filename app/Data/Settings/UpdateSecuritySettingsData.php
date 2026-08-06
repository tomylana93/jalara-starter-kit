<?php

namespace App\Data\Settings;

final readonly class UpdateSecuritySettingsData
{
    public function __construct(
        public int $maxFailedLoginAttempts,
        public int $suspensionDurationMinutes,
        public bool $maintenanceEnabled,
    ) {}
}
