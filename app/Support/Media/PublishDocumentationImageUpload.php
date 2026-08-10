<?php

namespace App\Support\Media;

use App\Enums\UserStatus;
use App\Models\Documentation;
use App\Models\ImageUpload;
use App\Models\User;
use App\Support\DocumentationContent;
use Illuminate\Support\Facades\Gate;

/**
 * Publishes an image an author dropped into the documentation editor.
 *
 * This is the one publication with no domain record to write to. The editor
 * uploads before the document exists — a new document has no id while it is
 * being written — so the image is published on its own and the author's next
 * save is what turns it into a referenced file. Until that save happens the
 * result is simply an unreferenced image, which is exactly what the orphan
 * sweep is for.
 */
final class PublishDocumentationImageUpload implements ImageUploadPublication
{
    public function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        if ($owner->status !== UserStatus::Active) {
            return false;
        }

        /*
         * Re-asked rather than assumed: the role that let this upload in may
         * have been taken away while it sat in the queue.
         */
        return Gate::forUser($owner)->allows('create', Documentation::class);
    }

    public function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return sprintf('%s/%s', DocumentationContent::IMAGE_DIRECTORY, $upload->getKey());
    }

    public function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        /*
         * Nothing to apply. `CompleteImageUpload` records the result path in
         * the same transaction, and `ImageUploadResource` is what hands the URL
         * back to the editor.
         */
    }
}
