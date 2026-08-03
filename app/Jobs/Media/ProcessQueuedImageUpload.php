<?php

namespace App\Jobs\Media;

use App\Actions\Media\FailImageUpload;
use App\Models\ImageUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

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

    public function handle(\App\Actions\Media\ProcessQueuedImageUpload $action): void
    {
        $action->handle($this->upload);
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
}
