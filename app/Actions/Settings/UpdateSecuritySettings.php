<?php

namespace App\Actions\Settings;

use App\Settings\SecuritySettings;

final class UpdateSecuritySettings
{
    /**
     * Update the security settings.
     *
     * @param  array{maxFailedLoginAttempts: int, suspensionDurationMinutes: int, maintenanceEnabled: bool}  $attributes
     */
    public function handle(SecuritySettings $settings, array $attributes): SecuritySettings
    {
        $settings->maxFailedLoginAttempts = $attributes['maxFailedLoginAttempts'];
        $settings->suspensionDurationMinutes = $attributes['suspensionDurationMinutes'];
        $settings->maintenanceEnabled = $attributes['maintenanceEnabled'];

        $settings->save();

        return $settings;
    }
}
