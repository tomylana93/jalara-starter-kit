<?php

namespace App\Enums;

/**
 * The lifecycle of a single backup run.
 *
 * `Pending` and `Running` are the only states that hold the single-flight lock;
 * both terminal states release it, which is what allows the next run to start.
 */
enum BackupRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * Whether the run has reached a state it can never leave.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending, self::Running => false,
            self::Completed, self::Failed => true,
        };
    }

    /**
     * The states a run may still transition out of.
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Running];
    }
}
