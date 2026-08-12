<?php

namespace App\Support\Ci;

/**
 * Whether a GitHub Release exists for a tag, and whether it counts.
 *
 * A draft is deliberately not a release: it is invisible to anybody consuming
 * the deployment contract, so a run that finds one has work left to do.
 */
enum ReleaseState: string
{
    case Missing = 'missing';
    case Draft = 'draft';
    case Published = 'published';

    /**
     * `gh release view --json isDraft` prints `true` or `false`, and the
     * workflow substitutes `missing` when there is no release to view.
     */
    public static function fromLookup(string $value): self
    {
        return match ($value) {
            'false' => self::Published,
            'true' => self::Draft,
            default => self::Missing,
        };
    }
}
