<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Media\StageImageUpload;
use App\Actions\Settings\RemoveBrandingAsset;
use App\Actions\Settings\UpdateBrandingSettings;
use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingAsset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPairPreset;
use App\Enums\ImageUploadTarget;
use App\Exceptions\Media\ActiveImageUploadExists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingAssetRequest;
use App\Http\Requests\Settings\UpdateBrandingSettingsRequest;
use App\Http\Resources\ImageUploadResource;
use App\Jobs\Media\ProcessBrandingImageUpload;
use App\Settings\BrandingSettings;
use Illuminate\Http\JsonResponse;
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
                'fontPair' => $settings->fontPair->value,
            ],
            'identityModeOptions' => BrandingIdentityMode::options(),
            'authLayoutOptions' => AuthLayoutPreset::options(),
            'appLayoutOptions' => AppLayoutPreset::options(),
            'colorThemeOptions' => ColorThemePreset::options(),
            'fontPairOptions' => FontPairPreset::options(),
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
        $updateBrandingSettings->handle($settings, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.branding.message.updated')]);

        return to_route('settings.branding.edit');
    }

    /**
     * Store a new image for a branding asset.
     *
     * Images use their own endpoint because a multipart body cannot be sent
     * reliably through the spoofed PUT the settings form uses.
     *
     * The upload is accepted rather than applied: the currently published asset
     * keeps serving until the queue has a processed replacement for it.
     */
    public function storeAsset(
        UpdateBrandingAssetRequest $request,
        BrandingAsset $asset,
        StageImageUpload $stageImageUpload,
    ): JsonResponse {
        try {
            $upload = $stageImageUpload->handle(
                $request->user(),
                $request->file('image'),
                ImageUploadTarget::Branding,
                $asset->value,
            );
        } catch (ActiveImageUploadExists $activeImageUploadExists) {
            $existing = $activeImageUploadExists->existing;

            /*
             * Branding is one shared slot per asset, so the upload in the way
             * may well be another administrator's. Their upload is private to
             * them: its status and cancellation endpoints answer only its
             * owner, so handing them over would promise a conversation that can
             * only end in 403. A second administrator is told the slot is busy
             * and nothing else.
             */
            if ($existing->user_id !== $request->user()?->getKey()) {
                return response()->json([
                    'message' => __('media.upload.message.conflict_other_owner'),
                ], 409);
            }

            /* The same person, most likely in another tab: theirs to resume. */
            return new ImageUploadResource($existing)->response()->setStatusCode(409);
        }

        dispatch(new ProcessBrandingImageUpload($upload));

        return new ImageUploadResource($upload)->response()->setStatusCode(202);
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
