<?php

namespace Database\Factories;

use App\Enums\BackupRunStatus;
use App\Enums\BackupRunType;
use App\Models\BackupRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupRun>
 */
class BackupRunFactory extends Factory
{
    protected $model = BackupRun::class;

    /**
     * A finished run, because that is what most history looks like.
     *
     * The default holds no lock: an active run is the exceptional state and has
     * to be asked for, so a careless factory call can never block the next
     * backup by accident.
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'lock_key' => null,
            'type' => BackupRunType::Backup,
            'status' => BackupRunStatus::Completed,
            'filename' => fake()->date('Y-m-d-H-i-s').'.zip',
            'size_in_bytes' => fake()->numberBetween(1024, 1024 * 1024),
            'error_code' => null,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }

    /**
     * A run still in flight, holding the single-flight lock.
     */
    public function active(): static
    {
        return $this->state(fn (): array => [
            'lock_key' => BackupRun::ACTIVE_LOCK_KEY,
            'status' => BackupRunStatus::Running,
            'filename' => null,
            'size_in_bytes' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * A restore of an existing archive rather than a backup that produced one.
     */
    public function restore(): static
    {
        return $this->state(fn (): array => [
            'type' => BackupRunType::Restore,
        ]);
    }

    /**
     * A run that failed and released its lock.
     */
    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => BackupRunStatus::Failed,
            'filename' => null,
            'size_in_bytes' => null,
            'error_code' => 'failed',
        ]);
    }
}
