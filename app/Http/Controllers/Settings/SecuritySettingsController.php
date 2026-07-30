<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateSecuritySettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSecuritySettingsRequest;
use App\Settings\SecuritySettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    /**
     * Show the security settings page.
     */
    public function edit(SecuritySettings $settings): Response
    {
        return Inertia::render('settings/Security', [
            'settings' => [
                'maxFailedLoginAttempts' => $settings->maxFailedLoginAttempts,
                'suspensionDurationMinutes' => $settings->suspensionDurationMinutes,
                'maintenanceEnabled' => $settings->maintenanceEnabled,
            ],
        ]);
    }

    /**
     * Update the security settings.
     */
    public function update(
        UpdateSecuritySettingsRequest $request,
        SecuritySettings $settings,
        UpdateSecuritySettings $updateSecuritySettings,
    ): RedirectResponse {
        $updateSecuritySettings->handle($settings, [
            'maxFailedLoginAttempts' => (int) $request->validated('maxFailedLoginAttempts'),
            'suspensionDurationMinutes' => (int) $request->validated('suspensionDurationMinutes'),
            'maintenanceEnabled' => $request->boolean('maintenanceEnabled'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.security.message.updated')]);

        return to_route('settings.security.edit');
    }
}
