<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Models\ImageUpload;
use Illuminate\Support\Facades\Storage;

/**
 * Cancels an upload on its owner's behalf, best effort.
 *
 * Cancellation is a request, not a guarantee: a worker may already be past the
 * point where it checks. The conditional update is what keeps that honest —
 * it only succeeds while the upload is still active, so an upload that has
 * already finished stays finished and its result is not thrown away.
 */
final class CancelImageUpload
{
    /**
     * Returns false when the upload had already reached a terminal state.
     */
    public function handle(ImageUpload $upload): bool
    {
        $cancelled = ImageUpload::query()
            ->whereKey($upload->getKey())
            ->active()
            ->update([
                'status' => ImageUploadStatus::Cancelled,
                'lock_key' => null,
                'payload' => null,
                'completed_at' => now(),
            ]);

        if ($cancelled === 0) {
            return false;
        }

        Storage::disk(ImageUpload::SOURCE_DISK)->delete($upload->source_path);

        $upload->refresh();

        return true;
    }
}
