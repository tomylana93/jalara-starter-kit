<?php

namespace App\Actions\Settings;

use App\Settings\MailSettings;

final class UpdateMailSettings
{
    /**
     * Update the mail settings.
     *
     * @param  array{fromName: string, fromAddress: string}  $attributes
     */
    public function handle(MailSettings $settings, array $attributes): MailSettings
    {
        $settings->fromName = $attributes['fromName'];
        $settings->fromAddress = $attributes['fromAddress'];

        $settings->save();

        return $settings;
    }
}
