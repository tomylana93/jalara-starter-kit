<?php

namespace App\Jobs\Media;

use App\Actions\Media\SwapStoredImagePath;
use App\Enums\UserStatus;
use App\Models\ImageUpload;
use App\Models\User;

/**
 * Publishes a processed avatar onto the account that uploaded it.
 *
 * The previous avatar keeps serving right up until the new path is saved, so a
 * failed or cancelled replacement leaves the account looking exactly as it did.
 */
class ProcessAvatarImageUpload extends ProcessQueuedImageUpload
{
    /**
     * An account that can no longer sign in does not get a new avatar.
     */
    protected function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        return $owner->status === UserStatus::Active;
    }

    protected function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return sprintf('avatars/%s', $owner->getKey());
    }

    protected function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        app(SwapStoredImagePath::class)->handle(
            $upload->target->disk(),
            $path,
            $owner->avatar_path,
            function (string $storedPath) use ($owner): void {
                $owner->avatar_path = $storedPath;
                $owner->save();
            },
        );
    }
}
