<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateBrandingSettings;
use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingSettingsRequest;
use App\Settings\BrandingSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingsController extends Controller
{
    /**
     * Show the branding settings page.
     */
    public function edit(BrandingSettings $settings): Response
    {
        return Inertia::render('settings/Branding', [
            'settings' => [
                'companyName' => $settings->companyName,
                'footerText' => $settings->footerText,
                'authLayout' => $settings->authLayout->value,
                'appLayout' => $settings->appLayout->value,
                'colorTheme' => $settings->colorTheme->value,
                'fontPreset' => $settings->fontPreset->value,
            ],
            'authLayoutOptions' => AuthLayoutPreset::options(),
            'appLayoutOptions' => AppLayoutPreset::options(),
            'colorThemeOptions' => ColorThemePreset::options(),
            'fontPresetOptions' => FontPreset::options(),
        ]);
    }

    /**
     * Update the branding settings.
     */
    public function update(
        UpdateBrandingSettingsRequest $request,
        BrandingSettings $settings,
        UpdateBrandingSettings $updateBrandingSettings,
    ): RedirectResponse {
        $updateBrandingSettings->handle($settings, [
            'companyName' => (string) $request->validated('companyName'),
            'footerText' => $request->validated('footerText'),
            'authLayout' => (string) $request->validated('authLayout'),
            'appLayout' => (string) $request->validated('appLayout'),
            'colorTheme' => (string) $request->validated('colorTheme'),
            'fontPreset' => (string) $request->validated('fontPreset'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.branding.message.updated')]);

        return to_route('settings.branding.edit');
    }
}
