<?php

namespace App\Actions\Media;

use App\Models\ImageUpload;
use App\Models\User;

interface ImageUploadPublication
{
    /**
     * Whether the owner may still publish to this target.
     */
    public function authorizePublication(ImageUpload $upload, User $owner): bool;

    /**
     * Where the processed image is stored.
     */
    public function destinationDirectory(ImageUpload $upload, User $owner): string;

    /**
     * Apply the stored result to the application.
     */
    public function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void;
}
