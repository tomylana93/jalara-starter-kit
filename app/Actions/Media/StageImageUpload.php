<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Takes a validated upload off the request and parks it for the queue.
 *
 * The bytes go to a private disk under a generated name, so nothing the user
 * sent is ever reachable over HTTP or able to influence the storage path. Only
 * once they are safely staged is the tracking row created, and the row's unique
 * lock is what refuses a second upload for a target that already has one.
 */
final class StageImageUpload
{
    /**
     * Stage the upload and open its lifecycle record.
     *
     * @param  array<string, mixed>  $payload  target-specific data the job needs to publish
     *
     * @throws ActiveImageUploadExists
     */
    public function handle(
        User $user,
        UploadedFile $file,
        ImageUploadTarget $target,
        ?string $targetKey = null,
        array $payload = [],
    ): ImageUpload {
        $disk = Storage::disk(ImageUpload::SOURCE_DISK);

        $sourcePath = $file->storeAs(
            ImageUpload::SOURCE_DIRECTORY,
            Str::uuid7()->toString().'.'.$file->extension(),
            ImageUpload::SOURCE_DISK,
        );

        throw_unless(is_string($sourcePath), StoredImageWriteFailed::class, ImageUpload::SOURCE_DIRECTORY);

        $upload = new ImageUpload;
        $upload->forceFill([
            'user_id' => $user->getKey(),
            'target' => $target,
            'target_key' => $targetKey,
            'lock_key' => ImageUpload::lockKeyFor($target, (string) $user->getKey(), $targetKey),
            'status' => ImageUploadStatus::Pending,
            'source_path' => $sourcePath,
            'source_mime_type' => (string) $file->getMimeType(),
            'payload' => $payload === [] ? null : $payload,
        ]);

        try {
            $upload->save();
        } catch (UniqueConstraintViolationException) {
            /*
             * Someone else holds the target. The staged bytes are useless now,
             * so they go straight back out rather than waiting for the sweep.
             */
            $disk->delete($sourcePath);

            throw new ActiveImageUploadExists($this->existingFor($target, $user, $targetKey));
        }

        return $upload;
    }

    /**
     * Read back the upload currently holding the target.
     */
    private function existingFor(ImageUploadTarget $target, User $user, ?string $targetKey): ImageUpload
    {
        return ImageUpload::query()
            ->where('lock_key', ImageUpload::lockKeyFor($target, (string) $user->getKey(), $targetKey))
            ->firstOrFail();
    }
}
