<?php

namespace App\Actions\Media;

use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Exceptions\Media\ImageUploadNoLongerPublishable;
use App\Models\ImageUpload;
use App\Models\User;
use App\Support\Media\ImageUploadPublication;
use App\Support\Media\PublishAvatarImageUpload;
use App\Support\Media\PublishBrandingImageUpload;
use App\Support\Media\PublishChatImageUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class ProcessQueuedImageUpload
{
    public function __construct(
        private ClaimImageUpload $claimImageUpload,
        private ProcessImageUpload $processImageUpload,
        private CompleteImageUpload $completeImageUpload,
        private FailImageUpload $failImageUpload,
        private PublishAvatarImageUpload $publishAvatarImageUpload,
        private PublishBrandingImageUpload $publishBrandingImageUpload,
        private PublishChatImageUpload $publishChatImageUpload,
    ) {}

    /**
     * Process and publish the queued image upload.
     */
    public function handle(ImageUpload $upload): void
    {
        $upload = $upload->fresh();

        if (! $upload instanceof ImageUpload) {
            return;
        }

        $owner = $upload->user;

        if (! $owner instanceof User) {
            $this->failImageUpload->handle($upload, FailImageUpload::REASON_TARGET_UNAVAILABLE);

            return;
        }

        /* Cancelled before the worker got here, or already finished. */
        if (! $this->claimImageUpload->handle($upload)) {
            return;
        }

        $publication = $this->publicationFor($upload->target);

        if (! $publication->authorizePublication($upload, $owner)) {
            $this->failImageUpload->handle($upload, FailImageUpload::REASON_UNAUTHORIZED);

            return;
        }

        $result = $this->processImageUpload->handle($upload, $publication->destinationDirectory($upload, $owner));

        $upload->refresh();

        /*
         * Everything from here re-validates against the state as it is *now*.
         * A result that may no longer be published is deleted rather than left
         * behind for the orphan sweep to find.
         */
        if ($upload->status !== ImageUploadStatus::Processing) {
            $this->discardResult($upload, $result['path']);

            return;
        }

        if (! $publication->authorizePublication($upload, $owner)) {
            $this->discardResult($upload, $result['path']);
            $this->failImageUpload->handle($upload, FailImageUpload::REASON_UNAUTHORIZED);

            return;
        }

        try {
            DB::transaction(function () use ($upload, $owner, $result, $publication): void {
                $publication->publish($upload, $owner, $result['path'], $result['mime_type']);

                /*
                 * Refusing here means the owner cancelled while the image was
                 * being processed. Throwing rolls the publication back with it,
                 * which is the only way the two stay consistent.
                 */
                throw_unless(
                    $this->completeImageUpload->handle($upload, $result['path'], $result['mime_type']),
                    ImageUploadNoLongerPublishable::class,
                );
            });
        } catch (ImageUploadNoLongerPublishable) {
            $this->discardResult($upload, $result['path']);

            return;
        } catch (Throwable $throwable) {
            $this->discardResult($upload, $result['path']);

            throw $throwable;
        }

        /* Committed. The staged source has nothing left to be retried from. */
        Storage::disk(ImageUpload::SOURCE_DISK)->delete($upload->source_path);
    }

    /**
     * Get the publication strategy for the targeted upload.
     */
    private function publicationFor(ImageUploadTarget $target): ImageUploadPublication
    {
        return match ($target) {
            ImageUploadTarget::Avatar => $this->publishAvatarImageUpload,
            ImageUploadTarget::Branding => $this->publishBrandingImageUpload,
            ImageUploadTarget::ChatImage => $this->publishChatImageUpload,
        };
    }

    /**
     * Remove a result that turned out not to be publishable.
     */
    private function discardResult(ImageUpload $upload, string $path): void
    {
        Storage::disk($upload->target->disk())->delete($path);
    }
}
