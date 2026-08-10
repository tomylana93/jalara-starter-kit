<?php

namespace App\Models;

use App\Enums\BrandingAsset;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Models\Chat\Message;
use Carbon\CarbonInterface;
use Database\Factories\ImageUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user-submitted image working its way through the queue.
 *
 * The row is the single source of truth for the upload: it owns the state the
 * client polls, the private staging copy the job reads, the published result,
 * and the lock that stops a second upload racing the same avatar or branding
 * slot. Nothing here is ever shown to the client directly — `ImageUploadResource`
 * decides what leaves the server.
 *
 * @property string $id
 * @property string $user_id
 * @property ImageUploadTarget $target
 * @property string|null $target_key
 * @property string|null $lock_key
 * @property ImageUploadStatus $status
 * @property string $source_path
 * @property string $source_mime_type
 * @property string|null $result_path
 * @property string|null $result_mime_type
 * @property array<string, mixed>|null $payload
 * @property string|null $error_code
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User|null $user
 * @property-read Message|null $resultMessage
 */
#[Hidden(['user_id', 'lock_key', 'source_path', 'source_mime_type', 'result_path', 'payload'])]
class ImageUpload extends Model
{
    /** @use HasFactory<ImageUploadFactory> */
    use HasFactory, HasUuids, MassPrunable;

    /**
     * How long a finished upload is kept so a returning client can still read
     * its outcome.
     */
    public const int RETENTION_HOURS = 24;

    /**
     * The disk the untouched upload is staged on until the job consumes it.
     */
    public const string SOURCE_DISK = 'local';

    /**
     * The private directory holding staged uploads, and the only temporary
     * prefix the orphan sweep is allowed to touch.
     */
    public const string SOURCE_DIRECTORY = 'image-uploads';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The chat message a finished upload produced, if it produced one.
     *
     * A ready chat upload records the created message id in `target_key`, which
     * makes the result graph eager-loadable before it is presented.
     *
     * @return BelongsTo<Message, $this>
     */
    public function resultMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'target_key');
    }

    /**
     * Uploads that still hold their target and may still change state.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', ImageUploadStatus::active());
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function terminal(Builder $query): void
    {
        $query->whereNotIn('status', ImageUploadStatus::active());
    }

    /**
     * The branding asset this upload targets, when it targets one.
     */
    public function brandingAsset(): ?BrandingAsset
    {
        if ($this->target !== ImageUploadTarget::Branding || $this->target_key === null) {
            return null;
        }

        return BrandingAsset::tryFrom($this->target_key);
    }

    /**
     * The box the processed image is scaled down into.
     *
     * @return array{0: positive-int, 1: positive-int}
     */
    public function maxDimensions(): array
    {
        return $this->target->maxDimensions($this->brandingAsset());
    }

    /**
     * The lock value an exclusive target holds while an upload is active.
     *
     * Chat and documentation images are not exclusive and therefore never take
     * a lock, which is what allows several pending messages — or several images
     * dropped into one document — to be uploaded at once.
     */
    public static function lockKeyFor(ImageUploadTarget $target, string $userId, ?string $targetKey): ?string
    {
        if (! $target->isExclusive()) {
            return null;
        }

        /*
         * Branding is one global slot per asset, so the owner is deliberately
         * left out: two administrators must not be able to replace the same
         * logo concurrently.
         */
        return match ($target) {
            ImageUploadTarget::Avatar => sprintf('avatar:%s', $userId),
            ImageUploadTarget::Branding => sprintf('branding:%s', $targetKey),
            ImageUploadTarget::ChatImage, ImageUploadTarget::DocumentationImage => null,
        };
    }

    /**
     * Finished uploads outlive their result only by the retention window.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->terminal()
            ->where('completed_at', '<=', now()->subHours(self::RETENTION_HOURS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => ImageUploadTarget::class,
            'status' => ImageUploadStatus::class,
            'payload' => 'encrypted:array',
            'completed_at' => 'datetime',
        ];
    }
}
