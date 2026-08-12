<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use App\Support\Ci\PullRequestPolicy;
use App\Support\Ci\RemediationEvidence;
use App\Support\Ci\Review;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Decides whether the history a release would cover may be released.
 *
 * Nothing on the Free plan can stop an invalid commit from reaching `main`, so
 * this is where it is stopped instead: an unexplained direct push leaves the
 * branch ineligible, which keeps the release pull request unchanged and the
 * publisher idle until the commit is reverted or recorded as remediated.
 *
 * The workflow collects the provenance report over the range and passes it in;
 * every judgement below is made from that document alone.
 */
#[Signature('ci:release-eligibility {--report= : Path to the provenance report JSON} {--ledger=.github/release-provenance.json : Path to the remediation ledger}')]
#[Description('Verify that every commit since the baseline may be released.')]
final class CheckReleaseEligibility extends Command
{
    /**
     * How an invalid commit can have been undone. Only `revert` remains: it is
     * the one remediation a machine can prove, because the remediating commit
     * must be exactly the inverse of the offending one. A `superseded` label
     * asserts replacement semantics nothing can verify, so it is not accepted.
     *
     * @var list<string>
     */
    private const array REMEDIATION_TYPES = ['revert'];

    public function handle(): int
    {
        $report = CiPayload::readFile((string) $this->option('report'));
        $ledgerPath = (string) $this->option('ledger');

        $ledger = is_file($ledgerPath) ? CiPayload::readFile($ledgerPath) : [];

        $commits = CiPayload::maps($report, 'commits');

        /** @var array<string, list<string>> $faults */
        $faults = [];

        foreach ($commits as $commit) {
            $faults[CiPayload::string($commit, 'sha')] = $this->commitProblems($commit);
        }

        [$problems, $remediated] = $this->reviewLedger($ledger, CiPayload::maps($report, 'remediations'), $faults);

        foreach ($faults as $sha => $commitFaults) {
            if (! in_array($sha, $remediated, true)) {
                $problems = [...$problems, ...$commitFaults];
            }
        }

        foreach ($problems as $problem) {
            $this->line("::error::{$problem}");
        }

        if ($problems !== []) {
            $this->line('Revert the offending commits, or record them in the remediation ledger through a pull request.');

            return self::FAILURE;
        }

        $baseline = CiPayload::string($report, 'baseline');
        $this->line(sprintf(
            '%d commit(s) since %s are eligible for release.',
            count($commits),
            $baseline === '' ? 'the baseline' : $this->short($baseline),
        ));

        return self::SUCCESS;
    }

    /**
     * The ledger exempts a commit only when a machine can prove the exemption:
     * the entry names a remediating commit that comes after it in the same
     * range, that is itself releasable, and that is exactly the inverse of the
     * offending commit, as verified with git by the provenance report.
     *
     * Without that evidence the ledger would be an allowlist: one line added in
     * a pull request would make any commit releasable without undoing anything
     * it did. The reason explains the entry to a human; the remediation is what
     * a machine can check.
     *
     * An entry whose offending commit already sits behind the effective
     * baseline was judged and published by an earlier run. It is closed rather
     * than an open exception, so resolved history stops being re-litigated —
     * and a leftover entry can never fail a run for naming commits the range
     * no longer contains.
     *
     * @param  array<array-key, mixed>  $ledger
     * @param  list<array<array-key, mixed>>  $evidence
     * @param  array<string, list<string>>  $faults
     * @return array{0: list<string>, 1: list<string>}
     */
    private function reviewLedger(array $ledger, array $evidence, array $faults): array
    {
        $problems = [];
        $remediated = [];

        if (! Str::isMatch('/^[0-9a-f]{40}$/', CiPayload::string($ledger, 'baseline'))) {
            $problems[] = 'The remediation ledger needs the full sha of its baseline commit.';
        }

        $entries = CiPayload::maps($ledger, 'remediated');

        if (count($entries) !== count($evidence)) {
            $problems[] = 'The provenance report does not carry evidence for every remediation ledger entry.';

            return [$problems, $remediated];
        }

        foreach ($entries as $index => $entry) {
            $label = "Remediation ledger entry #{$index}";
            $sha = CiPayload::string($entry, 'sha');
            $remediation = CiPayload::map($entry, 'remediation');
            $by = CiPayload::string($remediation, 'by');

            $entryProblems = [];

            if (! Str::isMatch('/^[0-9a-f]{40}$/', $sha)) {
                $entryProblems[] = "{$label} needs the full sha of the commit it remediates.";
            }

            if (Str::of(CiPayload::string($entry, 'reason'))->trim()->isEmpty()) {
                $entryProblems[] = "{$label} has no reason.";
            }

            if (! in_array(CiPayload::string($remediation, 'type'), self::REMEDIATION_TYPES, true)) {
                $entryProblems[] = sprintf(
                    '%s needs a remediation type of %s: a replacement cannot be proven from a label, so only an exact revert is accepted.',
                    $label,
                    implode(' or ', self::REMEDIATION_TYPES),
                );
            }

            if (! Str::isMatch('/^[0-9a-f]{40}$/', $by) || $by === $sha) {
                $entryProblems[] = "{$label} must name the full sha of the commit that remediated it.";
            }

            if ($entryProblems !== []) {
                $problems = [...$problems, ...$entryProblems];

                continue;
            }

            $proof = RemediationEvidence::fromArray($evidence[$index]);

            if (CiPayload::string($evidence[$index], 'sha') !== $sha || CiPayload::string($evidence[$index], 'by') !== $by) {
                $problems[] = "{$label} does not match the evidence the provenance report gathered for it.";

                continue;
            }

            if ($proof->isClosed()) {
                $this->line("{$label} is closed: {$this->short($sha)} is behind the effective baseline, so its resolution is already published.");

                continue;
            }

            if (! $proof->isOpen()) {
                $problems[] = "{$label} names {$this->short($sha)}, which is neither in the inspected range nor behind its baseline.";

                continue;
            }

            if (! array_key_exists($by, $faults)) {
                $entryProblems[] = "{$label} names {$this->short($by)}, which is not among the commits under review.";
            } elseif ($faults[$by] !== []) {
                $entryProblems[] = "{$label} names {$this->short($by)}, which is not itself a releasable commit.";
            }

            if ($proof->order !== true) {
                $entryProblems[] = "{$label} is not proven: {$this->short($by)} must come after {$this->short($sha)} in the inspected range.";
            }

            if ($proof->reverts !== true) {
                $entryProblems[] = "{$label} is not proven: {$this->short($by)} is not the exact inverse of {$this->short($sha)}.";
            }

            if ($entryProblems === []) {
                $remediated[] = $sha;

                continue;
            }

            $problems = [...$problems, ...$entryProblems];
        }

        return [$problems, $remediated];
    }

    /**
     * @param  array<array-key, mixed>  $commit
     * @return list<string>
     */
    private function commitProblems(array $commit): array
    {
        $sha = CiPayload::string($commit, 'sha');
        $short = $this->short($sha);
        $subject = CiPayload::string($commit, 'subject');

        if (count(CiPayload::strings($commit, 'parents')) !== 1) {
            return ["{$short} is not a squash merge: only single-parent commits may reach the default branch."];
        }

        $pullRequest = $this->mergingPullRequest($commit, $sha);

        if ($pullRequest === null) {
            return ["{$short} ({$subject}) was pushed directly: no merged pull request produced it."];
        }

        $number = CiPayload::integer($pullRequest, 'number');
        $label = $number === 0 ? $short : "{$short} (#{$number})";

        return array_map(
            static fn (string $violation): string => "{$label}: {$violation}",
            PullRequestPolicy::violations(
                CiPayload::string($pullRequest, 'title'),
                PullRequestPolicy::isFromFork($pullRequest),
                Review::fromArrays(CiPayload::maps($pullRequest, 'reviews')),
                CiPayload::string(CiPayload::map($pullRequest, 'head'), 'sha'),
            ),
        );
    }

    /**
     * The pull request that produced this commit is the one GitHub recorded the
     * commit against as its squash result. An associated pull request that
     * merely touches the commit — a reverted branch, a later revert — does not
     * count.
     *
     * @param  array<array-key, mixed>  $commit
     * @return array<array-key, mixed>|null
     */
    private function mergingPullRequest(array $commit, string $sha): ?array
    {
        foreach (CiPayload::maps($commit, 'pull_requests') as $pullRequest) {
            $merged = CiPayload::string($pullRequest, 'merged_at') !== '';

            if ($merged && CiPayload::string($pullRequest, 'merge_commit_sha') === $sha) {
                return $pullRequest;
            }
        }

        return null;
    }

    private function short(string $sha): string
    {
        return Str::substr($sha, 0, 12);
    }
}
