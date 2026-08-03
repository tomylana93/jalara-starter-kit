<?php

namespace App\Jobs\Media;

use App\Actions\Media\SwapStoredImagePath;
use App\Enums\BrandingAsset;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\BrandingSettings;

/**
 * Publishes a processed branding image into the application settings.
 *
 * Branding is a shared, application-wide slot, so the permission that opened
 * the upload is checked again here: an administrator whose access was revoked
 * while the image sat in the queue must not still be able to change the logo.
 */
class ProcessBrandingImageUpload extends ProcessQueuedImageUpload
{
    protected function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        if ($owner->status !== UserStatus::Active) {
            return false;
        }

        if (! $upload->brandingAsset() instanceof BrandingAsset) {
            return false;
        }

        return $owner->can(Permission::ManageSettings->value);
    }

    protected function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return $this->asset($upload)->directory();
    }

    protected function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        $settings = app(BrandingSettings::class);
        $property = $this->asset($upload)->property();

        app(SwapStoredImagePath::class)->handle(
            $upload->target->disk(),
            $path,
            $settings->{$property},
            function (string $storedPath) use ($settings, $property): void {
                $settings->{$property} = $storedPath;
                $settings->save();
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
