<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateSecuritySettingsData;
use App\Settings\SecuritySettings;

final class UpdateSecuritySettings
{
    /**
     * Update the security settings.
     */
    public function handle(SecuritySettings $settings, UpdateSecuritySettingsData $data): SecuritySettings
    {
        $settings->maxFailedLoginAttempts = $data->maxFailedLoginAttempts;
        $settings->suspensionDurationMinutes = $data->suspensionDurationMinutes;
        $settings->maintenanceEnabled = $data->maintenanceEnabled;

        $settings->save();

        return $settings;
    }
}
