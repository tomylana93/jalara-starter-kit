<?php

namespace App\Support\Ci;

/**
 * The merge policy this repository can enforce without any paid GitHub feature.
 *
 * On a private repository on the Free plan there are no protected branches,
 * rulesets, or required checks, so nothing stops a direct push or a merge over
 * a red gate. Release eligibility is the boundary instead: these are the rules a
 * commit has to satisfy before it may be released, applied both while a pull
 * request is open and again over the history that a release would cover.
 *
 * A branch inside the repository can only be pushed by an account with write
 * access, so a same-repository pull request already carries maintainer intent.
 * A fork does not, which is why review is required there and only there.
 */
final class PullRequestPolicy
{
    /**
     * The repository permission levels that carry write access.
     *
     * `author_association` deliberately plays no part here. `MEMBER` only says
     * the account belongs to the organisation that owns the repository, and
     * `COLLABORATOR` covers a read-only invitation, so either would accept an
     * approval from somebody who cannot push a branch here.
     *
     * @var list<string>
     */
    public const array WRITE_PERMISSIONS = ['admin', 'maintain', 'write'];

    /**
     * @param  array<array-key, mixed>  $pullRequest
     */
    public static function isFromFork(array $pullRequest): bool
    {
        $head = CiPayload::map($pullRequest, 'head');
        $base = CiPayload::map($pullRequest, 'base');

        $headRepository = CiPayload::string(CiPayload::map($head, 'repo'), 'full_name');
        $baseRepository = CiPayload::string(CiPayload::map($base, 'repo'), 'full_name');

        // A fork deleted before the check runs leaves no head repository to
        // compare, and the safe reading of an unknown origin is untrusted.
        if ($headRepository === '' || $baseRepository === '') {
            return true;
        }

        return $headRepository !== $baseRepository;
    }

    /**
     * An approval only counts for the revision it was given on, so any push to
     * the branch drops it, only while it is still the reviewer's latest
     * decision, and only for a reviewer whose resolved repository permission
     * carries write access.
     *
     * @param  list<Review>  $reviews
     */
    public static function hasApprovalWithWriteAccess(array $reviews, string $headSha): bool
    {
        return array_any(self::effectiveApprovals($reviews, $headSha), fn (Review $review) => $review->hasWriteAccess());
    }

    /**
     * @param  list<Review>  $reviews
     * @return list<string>
     */
    public static function violations(string $title, bool $fork, array $reviews, string $headSha): array
    {
        $violations = [];

        if (! ConventionalCommit::isValid($title)) {
            $violations[] = "Pull request title is not a Conventional Commit: {$title}";
        }

        if (! $fork || self::hasApprovalWithWriteAccess($reviews, $headSha)) {
            return $violations;
        }

        // Reporting the unreadable permission separately keeps a token that
        // could not resolve it from looking like a missing approval.
        $violations[] = self::hasUnresolvedApproval($reviews, $headSha)
            ? "An approving review of {$headSha} exists, but the reviewer's repository permission could not be read, so write access is unconfirmed."
            : "A pull request from a fork needs one approving review of {$headSha} from an account with write access.";

        return $violations;
    }

    /**
     * @param  list<Review>  $reviews
     */
    private static function hasUnresolvedApproval(array $reviews, string $headSha): bool
    {
        return array_any(self::effectiveApprovals($reviews, $headSha), fn (Review $review) => $review->hasUnresolvedPermission());
    }

    /**
     * The approval each reviewer currently stands behind for the exact head
     * revision.
     *
     * The history is reduced to every reviewer's latest decision first: a
     * later request for changes or a dismissal withdraws that reviewer's
     * earlier approval, while a comment review leaves it standing. Reviews
     * arrive in submission order from GitHub's API, so the last decision per
     * reviewer wins.
     *
     * @param  list<Review>  $reviews
     * @return list<Review>
     */
    private static function effectiveApprovals(array $reviews, string $headSha): array
    {
        if ($headSha === '') {
            return [];
        }

        $latest = [];

        foreach ($reviews as $review) {
            if ($review->state === null) {
                continue;
            }

            if (! $review->state->decides()) {
                continue;
            }

            $latest[$review->login] = $review;
        }

        $approvals = [];

        foreach ($latest as $review) {
            if ($review->state === ReviewState::Approved && $review->commitId === $headSha) {
                $approvals[] = $review;
            }
        }

        return $approvals;
    }
}
