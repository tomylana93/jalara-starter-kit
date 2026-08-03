<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Models\ImageUpload;

/**
 * Records the successful outcome of an upload and releases its target.
 *
 * This runs inside the same transaction as the domain change it describes, and
 * the transition is conditional on the upload still being `processing`. Those
 * two facts together are the whole guarantee: an owner who cancelled while the
 * image was being encoded cannot have `ready` written over their decision, and
 * a caller that sees this refuse knows the domain change is about to be rolled
 * back rather than left applied.
 *
 * Storage is deliberately untouched here. The staged bytes are the only copy of
 * the source, and deleting them before the transaction commits would leave a
 * retry with nothing to work from.
 */
final class CompleteImageUpload
{
    /**
     * Returns false when the upload is no longer `processing`.
     */
    public function handle(ImageUpload $upload, string $resultPath, string $resultMimeType): bool
    {
        $completed = ImageUpload::query()
            ->whereKey($upload->getKey())
            ->where('status', ImageUploadStatus::Processing)
            ->update([
                'status' => ImageUploadStatus::Ready,
                /* Publishing may have named what the upload produced. */
                'target_key' => $upload->target_key,
                'result_path' => $resultPath,
                'result_mime_type' => $resultMimeType,
                'error_code' => null,
                /* Releasing the lock lets the next upload for this target start. */
                'lock_key' => null,
                'payload' => null,
                'completed_at' => now(),
            ]);

        if ($completed === 0) {
            return false;
        }

        $upload->refresh();

        return true;
    }
}
