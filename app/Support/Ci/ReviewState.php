<?php

namespace App\Support\Ci;

/**
 * The states a pull request review can carry in GitHub's reviews API.
 *
 * Only a decision state moves a reviewer's standing: an approval, a request for
 * changes, or a dismissal. A comment review (`COMMENTED`) and a pending one
 * leave the reviewer's earlier decision untouched, which is exactly how
 * GitHub's own interface reads the history.
 */
enum ReviewState: string
{
    case Approved = 'APPROVED';
    case ChangesRequested = 'CHANGES_REQUESTED';
    case Dismissed = 'DISMISSED';
    case Commented = 'COMMENTED';

    /**
     * The payload value, or null for a state outside this vocabulary (for
     * example `PENDING`), which never decides anything either.
     */
    public static function fromPayload(string $state): ?self
    {
        return self::tryFrom($state);
    }

    /**
     * Whether the state supersedes the reviewer's earlier decision.
     */
    public function decides(): bool
    {
        return $this !== self::Commented;
    }
}
