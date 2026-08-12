<?php

use Illuminate\Support\Facades\File;

/*
 * Release eligibility is the security boundary. Nothing prevents a direct push
 * to the default branch here, so the branch is allowed to go red — what must
 * never happen is a release built on top of a commit nobody reviewed.
 */

afterEach(function (): void {
    File::deleteDirectory(sys_get_temp_dir().'/jalara-ci-eligibility');
});

/**
 * @param  array<array-key, mixed>  $contents
 */
function ciEligibilityFixture(string $name, array $contents): string
{
    $directory = sys_get_temp_dir().'/jalara-ci-eligibility';
    File::ensureDirectoryExists($directory);

    $path = "{$directory}/{$name}";
    File::put($path, (string) json_encode($contents));

    return $path;
}

/**
 * @param  array<string, mixed>  $overrides
 * @param  array<string, mixed>  $pullRequestOverrides
 * @return array<string, mixed>
 */
function ciSquashedCommit(array $overrides = [], array $pullRequestOverrides = []): array
{
    return array_merge([
        'sha' => 'c0ffee00c0ffee00c0ffee00c0ffee00c0ffee00',
        'subject' => 'feat(ci): add a trunk based gate (#42)',
        'parents' => ['1111111111111111111111111111111111111111'],
        'pull_requests' => [array_merge([
            'number' => 42,
            'title' => 'feat(ci): add a trunk based gate',
            'merged_at' => '2026-08-12T00:00:00Z',
            'merge_commit_sha' => 'c0ffee00c0ffee00c0ffee00c0ffee00c0ffee00',
            'head' => [
                'sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'repo' => ['full_name' => 'acme/app'],
            ],
            'base' => ['repo' => ['full_name' => 'acme/app']],
            'reviews' => [],
        ], $pullRequestOverrides)],
    ], $overrides);
}

/**
 * The commit that undoes another one: a squashed pull request like any other,
 * which is the whole point — a remediation is only evidence if it is itself
 * something this range would accept.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ciRevertCommit(array $overrides = []): array
{
    return ciSquashedCommit(array_merge([
        'sha' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
        'subject' => 'revert: the hotfix pushed during the incident (#43)',
    ], $overrides), [
        'number' => 43,
        'title' => 'revert: the hotfix pushed during the incident',
        'merge_commit_sha' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    ]);
}

/**
 * @param  list<array<string, mixed>>  $commits
 * @param  list<array<string, mixed>>  $remediations
 */
function ciProvenanceReport(array $commits, array $remediations = []): string
{
    return ciEligibilityFixture('report.json', [
        'baseline' => '1111111111111111111111111111111111111111',
        'head' => 'c0ffee00c0ffee00c0ffee00c0ffee00c0ffee00',
        'commits' => $commits,
        'remediations' => $remediations,
    ]);
}

/**
 * The evidence `.github/scripts/provenance-report.sh` gathers with git for one
 * ledger entry. The defaults describe a proven remediation — the offending
 * commit is in the range, the remediating commit follows it, and its patch is
 * the exact inverse — so a test only states the part it is about.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ciRemediationEvidence(string $sha, string $by, array $overrides = []): array
{
    return array_merge([
        'sha' => $sha,
        'by' => $by,
        'status' => 'open',
        'order' => true,
        'reverts' => true,
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $remediated
 */
function ciLedger(array $remediated): string
{
    return ciEligibilityFixture('ledger.json', [
        'baseline' => '1111111111111111111111111111111111111111',
        'remediated' => $remediated,
    ]);
}

it('releases a range whose commits all came from squashed pull requests', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit()]),
        '--ledger' => ciLedger([]),
    ]))->assertSuccessful();
});

it('releases an empty range', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([]),
        '--ledger' => ciLedger([]),
    ]))->assertSuccessful();
});

it('blocks a release when a commit was pushed straight to the branch', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit(['pull_requests' => []])]),
        '--ledger' => ciLedger([]),
    ]))->assertFailed();
});

it('blocks a release when a pull request landed as a merge commit', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit([
            'parents' => [
                '1111111111111111111111111111111111111111',
                '2222222222222222222222222222222222222222',
            ],
        ])]),
        '--ledger' => ciLedger([]),
    ]))->assertFailed();
});

it('blocks a release when the associated pull request did not produce the commit', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit([], [
            'merge_commit_sha' => '3333333333333333333333333333333333333333',
        ])]),
        '--ledger' => ciLedger([]),
    ]))->assertFailed();
});

it('blocks a release when a fork contribution was merged without an approval', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit([], [
            'head' => [
                'sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'repo' => ['full_name' => 'contributor/app'],
            ],
        ])]),
        '--ledger' => ciLedger([]),
    ]))->assertFailed();
});

it('releases a fork contribution approved by a maintainer', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit([], [
            'head' => [
                'sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'repo' => ['full_name' => 'contributor/app'],
            ],
            'reviews' => [[
                'state' => 'APPROVED',
                'commit_id' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'permission' => 'write',
            ]],
        ])]),
        '--ledger' => ciLedger([]),
    ]))->assertSuccessful();
});

/*
 * The ledger is evidence, not an allowlist. A line added in a pull request must
 * not be able to make an unreviewed commit releasable, so every entry is
 * answered by what `.github/scripts/provenance-report.sh` proved with git: the
 * remediating commit has to follow the offending one and its patch has to be
 * the exact inverse of it. The label the entry carries proves nothing.
 */
it('releases again once an invalid commit is recorded as remediated by a proven revert', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $revert = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $revert], [
            ciRemediationEvidence($invalid['sha'], $revert['sha']),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12; reverted in #43.',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertSuccessful();
});

it('rejects a remediation whose named commit did not revert anything', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $unrelated = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $unrelated], [
            // An unrelated but perfectly valid pull request, labelled `revert`.
            ciRemediationEvidence($invalid['sha'], $unrelated['sha'], ['reverts' => false]),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12.',
            'remediation' => ['type' => 'revert', 'by' => $unrelated['sha']],
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation whose revert precedes the commit it claims to undo', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $revert = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $revert], [
            ciRemediationEvidence($invalid['sha'], $revert['sha'], ['order' => false]),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12.',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation typed as a replacement, which nothing can prove', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $replacement = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $replacement], [
            ciRemediationEvidence($invalid['sha'], $replacement['sha']),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Rewritten properly in #43.',
            'remediation' => ['type' => 'superseded', 'by' => $replacement['sha']],
        ]]),
    ]))->assertFailed();
});

it('stops treating a remediation as an open exception once its resolution is published', function () {
    // The effective baseline has moved past the offending commit, so the range
    // no longer contains it and an earlier run already judged its resolution.
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit()], [
            ciRemediationEvidence(
                '9999999999999999999999999999999999999999',
                '8888888888888888888888888888888888888888',
                ['status' => 'closed', 'order' => null, 'reverts' => null],
            ),
        ]),
        '--ledger' => ciLedger([[
            'sha' => '9999999999999999999999999999999999999999',
            'reason' => 'Hotfix pushed during an incident, reverted, and released since.',
            'remediation' => ['type' => 'revert', 'by' => '8888888888888888888888888888888888888888'],
        ]]),
    ]))->assertSuccessful();
});

it('rejects a remediation naming a commit neither in the range nor behind the baseline', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit()], [
            ciRemediationEvidence(
                '9999999999999999999999999999999999999999',
                '8888888888888888888888888888888888888888',
                ['status' => 'unknown', 'order' => null, 'reverts' => null],
            ),
        ]),
        '--ledger' => ciLedger([[
            'sha' => '9999999999999999999999999999999999999999',
            'reason' => 'Names a commit this history does not contain.',
            'remediation' => ['type' => 'revert', 'by' => '8888888888888888888888888888888888888888'],
        ]]),
    ]))->assertFailed();
});

it('rejects a ledger entry the provenance report gathered no evidence for', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $revert = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $revert]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12.',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertFailed();
});

it('rejects evidence gathered for a different entry than the ledger records', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $revert = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $revert], [
            ciRemediationEvidence($invalid['sha'], '7777777777777777777777777777777777777777'),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12.',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation entry that names no remediating commit', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid], [
            ciRemediationEvidence($invalid['sha'], ''),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12.',
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation entry whose remediating commit is not under review', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid], [
            ciRemediationEvidence($invalid['sha'], '4444444444444444444444444444444444444444'),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12; reverted in #43.',
            'remediation' => ['type' => 'revert', 'by' => '4444444444444444444444444444444444444444'],
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation entry whose remediating commit was itself pushed directly', function () {
    $invalid = ciSquashedCommit(['pull_requests' => []]);
    $revert = ciRevertCommit(['pull_requests' => []]);

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$invalid, $revert], [
            ciRemediationEvidence($invalid['sha'], $revert['sha']),
        ]),
        '--ledger' => ciLedger([[
            'sha' => $invalid['sha'],
            'reason' => 'Hotfix pushed during the incident on 2026-08-12; reverted by hand.',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertFailed();
});

it('rejects a remediation entry that records no reason', function () {
    $revert = ciRevertCommit();

    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([$revert], [
            ciRemediationEvidence('c0ffee00c0ffee00c0ffee00c0ffee00c0ffee00', $revert['sha']),
        ]),
        '--ledger' => ciLedger([[
            'sha' => 'c0ffee00c0ffee00c0ffee00c0ffee00c0ffee00',
            'reason' => '  ',
            'remediation' => ['type' => 'revert', 'by' => $revert['sha']],
        ]]),
    ]))->assertFailed();
});

it('accepts the release commit that Release Please merges', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([ciSquashedCommit([
            'subject' => 'chore(main): release 2.2.0 (#69)',
        ], [
            'number' => 69,
            'title' => 'chore(main): release 2.2.0',
        ])]),
        '--ledger' => ciLedger([]),
    ]))->assertSuccessful();
});

it('reads the shipped remediation ledger', function () {
    pendingCommand($this->artisan('ci:release-eligibility', [
        '--report' => ciProvenanceReport([]),
    ]))->assertSuccessful();
});
