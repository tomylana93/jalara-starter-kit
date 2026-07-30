<?php

namespace App\Actions\Settings;

use App\Settings\UserProvisioningSettings;

final class ForgetDefaultPassword
{
    /**
     * Remove the configured default password.
     *
     * Clearing is an explicit operation: an empty form input keeps the current
     * password instead of deleting it.
     */
    public function handle(UserProvisioningSettings $settings): void
    {
        $settings->defaultPassword = null;

        $settings->save();
    }
}
