<?php

namespace App\Support\Ci;

/**
 * What publishing `vX.Y.Z` for a verified commit still has to do, given what
 * already exists.
 *
 * Publication is split into two independent facts — the tag and the GitHub
 * Release — because a run can die between them and leave either half missing.
 * Deciding both up front, from the current state rather than from how far a
 * previous run got, is what makes a retry finish the same version instead of
 * raising a new one or refusing forever.
 *
 * The tag is created at the verified sha directly. Asking a release tool to
 * work out for itself what to tag would mean the target is only known after the
 * ref has been written, and a tag on the wrong commit cannot be taken back
 * without breaking everyone who already fetched it.
 */
final readonly class TagPublication
{
    private function __construct(
        public bool $createTag,
        public bool $createRelease,
        public bool $publishDraft,
        public ?string $conflict,
        public string $summary,
    ) {}

    /**
     * @param  string  $tag  The tag this version publishes as.
     * @param  string  $candidateSha  The verified commit the tag must name.
     * @param  string  $taggedSha  The commit the tag already names, or empty when there is no such tag.
     * @param  ReleaseState  $release  Whether a GitHub Release already exists for the tag, and whether it is a draft.
     */
    public static function decide(string $tag, string $candidateSha, string $taggedSha, ReleaseState $release): self
    {
        // A tag that already names a different commit is never rewritten. It
        // means the version was published from somewhere else, and moving it
        // would silently change what everyone who already fetched it received.
        if ($taggedSha !== '' && $taggedSha !== $candidateSha) {
            return new self(false, false, false, sprintf(
                '%s already points at %s, not the verified release commit %s. Nothing was published.',
                $tag,
                Sha::short($taggedSha),
                Sha::short($candidateSha),
            ), '');
        }

        $createTag = $taggedSha === '';

        return new self(
            $createTag,
            $release === ReleaseState::Missing,
            $release === ReleaseState::Draft,
            null,
            self::describe($tag, $createTag, $release),
        );
    }

    public function blocked(): bool
    {
        return $this->conflict !== null;
    }

    /**
     * Whether everything the deployment contract promises already existed, so
     * this run changed nothing.
     */
    public function settled(): bool
    {
        return ! $this->blocked() && ! $this->createTag && ! $this->createRelease && ! $this->publishDraft;
    }

    private static function describe(string $tag, bool $createTag, ReleaseState $release): string
    {
        $tagHalf = $createTag ? "{$tag} is tagged at the verified commit" : "{$tag} was already tagged there";

        $releaseHalf = match ($release) {
            ReleaseState::Missing => 'the missing GitHub Release has been created',
            ReleaseState::Draft => 'its draft GitHub Release is now published',
            ReleaseState::Published => 'its GitHub Release was already published',
        };

        return "{$tagHalf}; {$releaseHalf}.";
    }
}
