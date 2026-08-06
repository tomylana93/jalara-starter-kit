<?php

namespace App\Models;

use App\Enums\BackupRunStatus;
use App\Enums\BackupRunType;
use Carbon\CarbonInterface;
use Database\Factories\BackupRunFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One backup or restore attempt, whether started by the schedule or by an
 * administrator.
 *
 * The row is the single source of truth for a run: the state the page polls,
 * the archive it produced, and the lock that stops two of them touching the same
 * data at once. Scheduled backups, manual backups and restores deliberately
 * share this table and this lock - separate paths would let them collide, and
 * would leave the history page describing only half of what the system did.
 *
 * @property string $id
 * @property string|null $user_id
 * @property string|null $lock_key
 * @property BackupRunType $type
 * @property BackupRunStatus $status
 * @property string|null $filename
 * @property int|null $size_in_bytes
 * @property string|null $error_code
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User|null $user
 */
class BackupRun extends Model
{
    /** @use HasFactory<BackupRunFactory> */
    use HasFactory, HasUuids, MassPrunable;

    /**
     * How long finished runs are kept.
     *
     * Comfortably longer than any archive retention an operator will see in the
     * list, so history never disappears while the archive it describes is still
     * on disk.
     */
    public const int RETENTION_DAYS = 90;

    /**
     * The single value the active-run lock takes.
     *
     * One fixed key rather than one per user or per type: the point is that the
     * application runs at most one backup or restore at a time, no matter who or
     * what asked for it.
     */
    public const string ACTIVE_LOCK_KEY = 'backup:run';

    /**
     * The administrator who started the run; null when the schedule did.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Runs that still hold the lock and may still change state.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', BackupRunStatus::active());
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function terminal(Builder $query): void
    {
        $query->whereNotIn('status', BackupRunStatus::active());
    }

    /**
     * Finished runs outlive their archive only by the retention window.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->terminal()
            ->where('completed_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BackupRunType::class,
            'status' => BackupRunStatus::class,
            'size_in_bytes' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
