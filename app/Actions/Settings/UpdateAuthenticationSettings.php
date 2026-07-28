<?php

namespace App\Actions\Settings;

use App\Enums\PasswordPolicy;
use App\Settings\AuthenticationSettings;

final class UpdateAuthenticationSettings
{
    /**
     * Update the authentication settings.
     *
     * @param  array{requireEmailVerification: bool, passwordPolicy: string, sessionLifetimeMinutes: int}  $attributes
     */
    public function handle(AuthenticationSettings $settings, array $attributes): AuthenticationSettings
    {
        $settings->requireEmailVerification = $attributes['requireEmailVerification'];
        $settings->passwordPolicy = PasswordPolicy::from($attributes['passwordPolicy']);
        $settings->sessionLifetimeMinutes = $attributes['sessionLifetimeMinutes'];

        $settings->save();

        return $settings;
    }
}
