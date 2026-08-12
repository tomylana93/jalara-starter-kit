<?php

namespace App\Support\Ci;

/**
 * What the publisher does about the commit it selected.
 *
 * Standing down and refusing are deliberately different outcomes. Every
 * ordinary commit on the default branch reaches the publisher and has nothing
 * to release, which is not a fault; an operator who asked for a reconciliation
 * and cannot have it has to be told loudly.
 */
enum PublicationDecision: string
{
    /** The commit is a verified release merge and may be tagged. */
    case Publish = 'publish';

    /** Nothing to do, and nothing wrong. */
    case Nothing = 'nothing';

    /** Publication was asked for and must not happen. */
    case Refuse = 'refuse';

    public function proceeds(): bool
    {
        return $this === self::Publish;
    }

    public function fails(): bool
    {
        return $this === self::Refuse;
    }
}
