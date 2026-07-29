<?php

namespace App\Actions\Settings;

use App\Actions\Media\DeleteStoredImage;
use App\Enums\BrandingAsset;
use App\Settings\BrandingSettings;

final readonly class RemoveBrandingAsset
{
    public function __construct(private DeleteStoredImage $deleteStoredImage) {}

    /**
     * Clear the stored image for the given branding asset.
     */
    public function handle(BrandingSettings $settings, BrandingAsset $asset): BrandingSettings
    {
        $property = $asset->property();

        $this->deleteStoredImage->handle(
            $settings->{$property},
            function () use ($settings, $property): void {
                $settings->{$property} = null;
                $settings->save();
            },
        );

        return $settings;
    }
}
