<?php

namespace App\Jobs\Media;

use App\Actions\Media\ClaimImageUpload;
use App\Actions\Media\CompleteImageUpload;
use App\Actions\Media\FailImageUpload;
use App\Actions\Media\ImageUploadNoLongerPublishable;
use App\Actions\Media\ProcessImageUpload;
use App\Enums\ImageUploadStatus;
use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The shared skeleton every queued image upload follows.
 *
 * The order of the steps is the whole point. Authorization is checked when the
 * request arrives *and* again here, immediately before the result is applied,
 * because a permission can be revoked while the image sits in the queue.
 * Cancellation is checked twice for the same reason: once before any work is
 * done, and once after processing but before anything is published, so an
 * upload the owner walked away from never lands.
 *
 * Nothing the target already holds is touched until the replacement is safely
 * stored, so a failure or a cancellation always leaves the existing image in
 * place. Each subclass supplies only what differs: who may publish, where the
 * result goes, and what publishing means.
 *
 * Publishing and marking the upload `ready` are one transaction. Half of that
 * pair is always wrong: an applied image whose upload still reads `processing`
 * would be republished by the next attempt, and a `ready` upload whose target
 * was never changed would tell the client to look at an image that is not
 * there. Committing them together is also what makes a retry safe, because an
 * attempt that failed part-way left nothing behind to collide with.
 */
abstract class ProcessQueuedImageUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Transient problems deserve a retry; a broken image does not deserve many.
     */
    public int $tries = 3;

    /**
     * Comfortably below the database queue's 90 second `retry_after`, so a slow
     * job is never picked up a second time while the first is still running.
     */
    public int $timeout = 60;

    public function __construct(public ImageUpload $upload) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(
        ClaimImageUpload $claimImageUpload,
        ProcessImageUpload $processImageUpload,
        CompleteImageUpload $completeImageUpload,
        FailImageUpload $failImageUpload,
    ): void {
        $upload = $this->upload->fresh();

        if (! $upload instanceof ImageUpload) {
            return;
        }

        $owner = $upload->user;

        if (! $owner instanceof User) {
            $failImageUpload->handle($upload, FailImageUpload::REASON_TARGET_UNAVAILABLE);

            return;
        }

        /* Cancelled before the worker got here, or already finished. */
        if (! $claimImageUpload->handle($upload)) {
            return;
        }

        if (! $this->authorizePublication($upload, $owner)) {
            $failImageUpload->handle($upload, FailImageUpload::REASON_UNAUTHORIZED);

            return;
        }

        $result = $processImageUpload->handle($upload, $this->destinationDirectory($upload, $owner));

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

        if (! $this->authorizePublication($upload, $owner)) {
            $this->discardResult($upload, $result['path']);
            $failImageUpload->handle($upload, FailImageUpload::REASON_UNAUTHORIZED);

            return;
        }

        try {
            DB::transaction(function () use ($upload, $owner, $result, $completeImageUpload): void {
                $this->publish($upload, $owner, $result['path'], $result['mime_type']);

                /*
                 * Refusing here means the owner cancelled while the image was
                 * being processed. Throwing rolls the publication back with it,
                 * which is the only way the two stay consistent.
                 */
                throw_unless(
                    $completeImageUpload->handle($upload, $result['path'], $result['mime_type']),
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
     * Give up for good: record the failure and release everything held.
     */
    public function failed(?Throwable $throwable): void
    {
        $upload = $this->upload->fresh();

        if ($upload instanceof ImageUpload) {
            app(FailImageUpload::class)->handle($upload, FailImageUpload::REASON_PROCESSING);
        }
    }

    /**
     * Whether the owner may still publish to this target.
     */
    abstract protected function authorizePublication(ImageUpload $upload, User $owner): bool;

    /**
     * Where the processed image is stored.
     */
    abstract protected function destinationDirectory(ImageUpload $upload, User $owner): string;

    /**
     * Apply the stored result to the application.
     */
    abstract protected function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void;

    /**
     * Remove a result that turned out not to be publishable.
     */
    private function discardResult(ImageUpload $upload, string $path): void
    {
        Storage::disk($upload->target->disk())->delete($path);
    }
}
