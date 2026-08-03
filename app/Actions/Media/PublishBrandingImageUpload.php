<?php

namespace App\Actions\Media;

use App\Enums\BrandingAsset;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\BrandingSettings;

final readonly class PublishBrandingImageUpload implements ImageUploadPublication
{
    public function __construct(
        private BrandingSettings $brandingSettings,
        private SwapStoredImagePath $swapStoredImagePath,
    ) {}

    public function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        if ($owner->status !== UserStatus::Active) {
            return false;
        }

        if (! $upload->brandingAsset() instanceof BrandingAsset) {
            return false;
        }

        return $owner->can(Permission::ManageSettings->value);
    }

    public function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return $this->asset($upload)->directory();
    }

    public function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        $property = $this->asset($upload)->property();

        $this->swapStoredImagePath->handle(
            $upload->target->disk(),
            $path,
            $this->brandingSettings->{$property},
            function (string $storedPath) use ($property): void {
                $this->brandingSettings->{$property} = $storedPath;
                $this->brandingSettings->save();
            },
        );
    }

    /**
     * The asset this upload targets, guaranteed present by the authorization check.
     */
    private function asset(ImageUpload $upload): BrandingAsset
    {
        return $upload->brandingAsset() ?? BrandingAsset::from((string) $upload->target_key);
    }
}
