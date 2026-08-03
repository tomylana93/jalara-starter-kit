<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Models\ImageUpload;
use Illuminate\Support\Facades\Storage;

/**
 * Records a final failure, releases the target, and drops the staged bytes.
 *
 * The stored code is a translation key the client may show; the exception that
 * caused it stays in the log. Whatever the target already had keeps working —
 * a failed replacement never removes the image it was going to replace.
 *
 * The transition is conditional so a worker giving up cannot overwrite an
 * outcome the owner already chose, such as a cancellation that landed first.
 */
final class FailImageUpload
{
    public const string REASON_PROCESSING = 'processing_failed';

    public const string REASON_UNAUTHORIZED = 'unauthorized';

    public const string REASON_TARGET_UNAVAILABLE = 'target_unavailable';

    /**
     * Returns false when the upload had already reached a terminal state.
     */
    public function handle(ImageUpload $upload, string $errorCode = self::REASON_PROCESSING): bool
    {
        $failed = ImageUpload::query()
            ->whereKey($upload->getKey())
            ->active()
            ->update([
                'status' => ImageUploadStatus::Failed,
                'error_code' => $errorCode,
                'lock_key' => null,
                'payload' => null,
                'completed_at' => now(),
            ]);

        /* The staged bytes are useless either way. */
        Storage::disk(ImageUpload::SOURCE_DISK)->delete($upload->source_path);

        if ($failed === 0) {
            return false;
        }

        $upload->refresh();

        return true;
    }
}
