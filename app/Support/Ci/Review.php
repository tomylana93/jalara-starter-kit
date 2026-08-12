<?php

namespace App\Support\Ci;

/**
 * One pull request review with the reviewer's resolved repository permission
 * attached.
 *
 * The review payload names a reviewer's relationship to the repository, not
 * what it lets them do, so the permission is resolved separately by
 * `.github/scripts/pull-request-reviews.sh` and carried here. A permission the
 * token could not read arrives as `unknown` and is reported distinctly from a
 * missing approval, so a token problem never reads as a policy violation.
 */
final readonly class Review
{
    public function __construct(
        public ?ReviewState $state,
        public string $commitId,
        public string $login,
        public string $permission,
    ) {}

    /**
     * @param  array<array-key, mixed>  $review
     */
    public static function fromArray(array $review): self
    {
        return new self(
            ReviewState::fromPayload(CiPayload::string($review, 'state')),
            CiPayload::string($review, 'commit_id'),
            CiPayload::string($review, 'login'),
            CiPayload::string($review, 'permission'),
        );
    }

    /**
     * @param  list<array<array-key, mixed>>  $reviews
     * @return list<self>
     */
    public static function fromArrays(array $reviews): array
    {
        return array_map(self::fromArray(...), $reviews);
    }

    /**
     * Whether the reviewer's resolved permission carries write access.
     *
     * `author_association` deliberately plays no part here. `MEMBER` only says
     * the account belongs to the organisation that owns the repository, and
     * `COLLABORATOR` covers a read-only invitation, so either would accept an
     * approval from somebody who cannot push a branch here.
     */
    public function hasWriteAccess(): bool
    {
        return in_array($this->permission, PullRequestPolicy::WRITE_PERMISSIONS, true);
    }

    public function hasUnresolvedPermission(): bool
    {
        return $this->permission === 'unknown';
    }
}
