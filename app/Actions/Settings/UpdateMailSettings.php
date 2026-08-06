<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateMailSettingsData;
use App\Settings\MailSettings;

final class UpdateMailSettings
{
    /**
     * Update the mail settings.
     */
    public function handle(MailSettings $settings, UpdateMailSettingsData $data): MailSettings
    {
        $settings->fromName = $data->fromName;
        $settings->fromAddress = $data->fromAddress;

        $settings->save();

        return $settings;
    }
}
