<?php

namespace App\Enums;

/**
 * The lifecycle of a queued image upload.
 *
 * `Pending` and `Processing` are the only states that hold the active-target
 * lock; everything else is terminal and is pruned once the retention window
 * has passed.
 */
enum ImageUploadStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * Whether the upload has reached a state it can never leave.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending, self::Processing => false,
            self::Ready, self::Failed, self::Cancelled => true,
        };
    }

    /**
     * The states an upload may still transition out of.
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Processing];
    }
}
