<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\RemoveBrandingAsset;
use App\Actions\Settings\UpdateBrandingAsset;
use App\Actions\Settings\UpdateBrandingSettings;
use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingAsset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingAssetRequest;
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
                'identityMode' => $settings->identityMode->value,
                'authLayout' => $settings->authLayout->value,
                'appLayout' => $settings->appLayout->value,
                'colorTheme' => $settings->colorTheme->value,
                'fontPreset' => $settings->fontPreset->value,
            ],
            'identityModeOptions' => BrandingIdentityMode::options(),
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
            'identityMode' => (string) $request->validated('identityMode'),
            'authLayout' => (string) $request->validated('authLayout'),
            'appLayout' => (string) $request->validated('appLayout'),
            'colorTheme' => (string) $request->validated('colorTheme'),
            'fontPreset' => (string) $request->validated('fontPreset'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.branding.message.updated')]);

        return to_route('settings.branding.edit');
    }

    /**
     * Store a new image for a branding asset.
     *
     * Images use their own endpoint because a multipart body cannot be sent
     * reliably through the spoofed PUT the settings form uses.
     */
    public function storeAsset(
        UpdateBrandingAssetRequest $request,
        BrandingAsset $asset,
        BrandingSettings $settings,
        UpdateBrandingAsset $updateBrandingAsset,
    ): RedirectResponse {
        $updateBrandingAsset->handle($settings, $asset, $request->file('image'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.branding.message.asset_updated')]);

        return to_route('settings.branding.edit');
    }

    /**
     * Remove the stored image for a branding asset.
     */
    public function destroyAsset(
        BrandingAsset $asset,
        BrandingSettings $settings,
        RemoveBrandingAsset $removeBrandingAsset,
    ): RedirectResponse {
        $removeBrandingAsset->handle($settings, $asset);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.branding.message.asset_removed')]);

        return to_route('settings.branding.edit');
    }
}
