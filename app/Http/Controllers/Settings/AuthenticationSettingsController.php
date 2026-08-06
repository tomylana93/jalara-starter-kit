<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateAuthenticationSettings;
use App\Enums\PasswordPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAuthenticationSettingsRequest;
use App\Settings\AuthenticationSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticationSettingsController extends Controller
{
    /**
     * Show the authentication settings page.
     */
    public function edit(AuthenticationSettings $settings): Response
    {
        return Inertia::render('settings/Authentication', [
            'settings' => [
                'requireEmailVerification' => $settings->requireEmailVerification,
                'passwordPolicy' => $settings->passwordPolicy->value,
                'sessionLifetimeMinutes' => $settings->sessionLifetimeMinutes,
            ],
            'passwordPolicyOptions' => PasswordPolicy::options(),
        ]);
    }

    /**
     * Update the authentication settings.
     */
    public function update(
        UpdateAuthenticationSettingsRequest $request,
        AuthenticationSettings $settings,
        UpdateAuthenticationSettings $updateAuthenticationSettings,
    ): RedirectResponse {
        $updateAuthenticationSettings->handle($settings, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.authentication.message.updated')]);

        return to_route('settings.authentication.edit');
    }
}
