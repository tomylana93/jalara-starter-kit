<?php

namespace App\Actions\Settings;

use App\Settings\UserProvisioningSettings;

final class UpdateDefaultPassword
{
    /**
     * Replace the password assigned to administratively created users.
     *
     * The value is never returned, flashed, or logged; callers only learn that
     * the write succeeded.
     */
    public function handle(UserProvisioningSettings $settings, string $defaultPassword): void
    {
        $settings->defaultPassword = $defaultPassword;

        $settings->save();
    }
}
