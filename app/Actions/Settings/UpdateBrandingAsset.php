<?php

namespace App\Actions\Settings;

use App\Actions\Media\ReplaceStoredImage;
use App\Enums\BrandingAsset;
use App\Settings\BrandingSettings;
use Illuminate\Http\UploadedFile;

final readonly class UpdateBrandingAsset
{
    public function __construct(private ReplaceStoredImage $replaceStoredImage) {}

    /**
     * Store a new image for the given branding asset.
     */
    public function handle(BrandingSettings $settings, BrandingAsset $asset, UploadedFile $file): BrandingSettings
    {
        $property = $asset->property();

        $this->replaceStoredImage->handle(
            $file,
            $asset->directory(),
            $settings->{$property},
            function (string $path) use ($settings, $property): void {
                $settings->{$property} = $path;
                $settings->save();
            },
        );

        return $settings;
    }
}
