<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateAuthenticationSettingsData;
use App\Settings\AuthenticationSettings;

final class UpdateAuthenticationSettings
{
    /**
     * Update the authentication settings.
     */
    public function handle(AuthenticationSettings $settings, UpdateAuthenticationSettingsData $data): AuthenticationSettings
    {
        $settings->requireEmailVerification = $data->requireEmailVerification;
        $settings->passwordPolicy = $data->passwordPolicy;
        $settings->sessionLifetimeMinutes = $data->sessionLifetimeMinutes;

        $settings->save();

        return $settings;
    }
}
