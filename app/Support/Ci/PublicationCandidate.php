<?php

namespace App\Support\Ci;

/**
 * Which commit, if any, the publisher may tag.
 *
 * Publication is the one operation here that cannot be undone quietly: a tag
 * and a GitHub Release are what the deployment contract points at. So the
 * commit is decided once, from evidence about that exact commit, and every step
 * afterwards addresses it by sha.
 *
 * Two things were previously left to timing and both are decided here instead.
 * A `workflow_run` publication used to compare the verified commit against the
 * tip of the default branch and stand down when the branch had moved, expecting
 * a later run to do the work — but the later run belongs to an ordinary commit
 * and stands down too, so the release was simply lost. And reconciliation used
 * to search back a fixed number of commits for something that looked like a
 * release merge, which both put older releases out of reach and let the search
 * choose the commit rather than the operator.
 */
final readonly class PublicationCandidate
{
    private function __construct(
        public PublicationDecision $decision,
        public string $sha,
        public string $reason,
        private bool $reconciliation,
    ) {}

    /**
     * The commit this run is about, before anything has been proven about it.
     *
     * @param  string  $event  The event that started the run.
     * @param  string  $runSha  The commit the `main` workflow run verified, for a `workflow_run`.
     * @param  string  $requestedSha  The commit an operator named, for a `workflow_dispatch`.
     */
    public static function select(string $event, string $runSha, string $requestedSha): self
    {
        if ($event === 'workflow_dispatch') {
            return $requestedSha === ''
                ? self::refuse('', 'Reconciliation needs the exact sha of the release commit to finish.')
                : self::consider($requestedSha, reconciliation: true);
        }

        // The sha the run verified, never the tip of the branch. The branch may
        // have moved on since, which changes nothing about what this run proved.
        return $runSha === ''
            ? self::refuse('', 'The triggering workflow run named no commit.')
            : self::consider($runSha);
    }

    /**
     * Whether the selected commit may actually be published, judged from what
     * was gathered about that same commit and nothing else.
     *
     * @param  bool  $releaseCommit  Whether the commit merged a Release Please pull request.
     * @param  bool  $gatePassed  Whether the commit has a successful `main` workflow run of its own.
     * @param  bool  $onDefaultBranch  Whether the commit is an ancestor of the default branch.
     */
    public function verify(bool $releaseCommit, bool $gatePassed, bool $onDefaultBranch): self
    {
        if (! $this->decision->proceeds()) {
            return $this;
        }

        $short = Sha::short($this->sha);

        if (! $onDefaultBranch) {
            return self::refuse($this->sha, "{$short} is not an ancestor of the default branch, so it may not be published.");
        }

        if (! $gatePassed) {
            return self::refuse($this->sha, "{$short} has no successful `main` run, so it may not be published.");
        }

        if (! $releaseCommit) {
            if ($this->reconciliation) {
                return self::refuse($this->sha, "{$short} did not merge a release pull request, so it cannot be reconciled.");
            }

            return new self(
                PublicationDecision::Nothing,
                $this->sha,
                "{$short} did not merge a release pull request; there is nothing to publish.",
                false,
            );
        }

        return new self(
            PublicationDecision::Publish,
            $this->sha,
            "{$short} merged a release pull request and passed its own gate.",
            $this->reconciliation,
        );
    }

    private static function consider(string $sha, bool $reconciliation = false): self
    {
        return Sha::isFull($sha)
            ? new self(PublicationDecision::Publish, $sha, "{$sha} is the candidate.", $reconciliation)
            : self::refuse($sha, "[{$sha}] is not the full 40-character sha of a commit.");
    }

    private static function refuse(string $sha, string $reason): self
    {
        return new self(PublicationDecision::Refuse, $sha, $reason, false);
    }
}
