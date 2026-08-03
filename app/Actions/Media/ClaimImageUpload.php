<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Models\ImageUpload;

/**
 * Moves an upload into `processing`, or reports that it may not be processed.
 *
 * The claim is a conditional update rather than a read followed by a write, so
 * a cancellation landing at the same moment as the worker always wins cleanly:
 * exactly one of them changes the row.
 */
final class ClaimImageUpload
{
    /**
     * Attempt to claim the upload for processing.
     *
     * Returns false when the upload has already reached a terminal state —
     * cancelled by its owner, or finished by an earlier attempt.
     */
    public function handle(ImageUpload $upload): bool
    {
        $claimed = ImageUpload::query()
            ->whereKey($upload->getKey())
            ->active()
            ->update(['status' => ImageUploadStatus::Processing]);

        if ($claimed === 0) {
            return false;
        }

        $upload->refresh();

        return true;
    }
}
