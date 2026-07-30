<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\ForgetDefaultPassword;
use App\Actions\Settings\UpdateDefaultPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDefaultPasswordRequest;
use App\Settings\UserProvisioningSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserProvisioningSettingsController extends Controller
{
    /**
     * Show the user provisioning settings page.
     *
     * Only the configured/not configured status crosses the boundary; the
     * stored password itself is never sent to the client.
     */
    public function edit(UserProvisioningSettings $settings): Response
    {
        return Inertia::render('settings/UserProvisioning', [
            'hasDefaultPassword' => $settings->hasDefaultPassword(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Replace the password assigned to administratively created users.
     */
    public function update(
        UpdateDefaultPasswordRequest $request,
        UserProvisioningSettings $settings,
        UpdateDefaultPassword $updateDefaultPassword,
    ): RedirectResponse {
        $updateDefaultPassword->handle($settings, (string) $request->validated('defaultPassword'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('setting.user_provisioning.default_password.updated'),
        ]);

        return to_route('settings.user-provisioning.edit');
    }

    /**
     * Remove the configured default password.
     */
    public function destroy(
        UserProvisioningSettings $settings,
        ForgetDefaultPassword $forgetDefaultPassword,
    ): RedirectResponse {
        $forgetDefaultPassword->handle($settings);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('setting.user_provisioning.default_password.removed'),
        ]);

        return to_route('settings.user-provisioning.edit');
    }
}
