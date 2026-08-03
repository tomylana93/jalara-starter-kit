<?php

namespace App\Actions\Media;

use App\Enums\UserStatus;
use App\Models\ImageUpload;
use App\Models\User;

final readonly class PublishAvatarImageUpload implements ImageUploadPublication
{
    public function __construct(
        private SwapStoredImagePath $swapStoredImagePath,
    ) {}

    public function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        return $owner->status === UserStatus::Active;
    }

    public function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return sprintf('avatars/%s', $owner->getKey());
    }

    public function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        $this->swapStoredImagePath->handle(
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
