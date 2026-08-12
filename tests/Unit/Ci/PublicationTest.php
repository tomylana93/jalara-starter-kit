<?php

use App\Support\Ci\PublicationCandidate;
use App\Support\Ci\PublicationDecision;
use App\Support\Ci\ReleaseState;
use App\Support\Ci\TagPublication;

const RELEASE_SHA = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const OTHER_SHA = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

it('publishes the workflow run sha even after main advances', function () {
    $candidate = PublicationCandidate::select('workflow_run', RELEASE_SHA, '')
        ->verify(releaseCommit: true, gatePassed: true, onDefaultBranch: true);

    expect($candidate->decision)->toBe(PublicationDecision::Publish)
        ->and($candidate->sha)->toBe(RELEASE_SHA);
});

it('requires an explicit full sha for reconciliation', function (string $sha) {
    $candidate = PublicationCandidate::select('workflow_dispatch', '', $sha);

    expect($candidate->decision)->toBe(PublicationDecision::Refuse);
})->with(['', 'main', 'abc123']);

it('refuses a candidate without complete provenance', function (bool $releaseCommit, bool $gatePassed, bool $onDefaultBranch) {
    $candidate = PublicationCandidate::select('workflow_dispatch', '', RELEASE_SHA)
        ->verify($releaseCommit, $gatePassed, $onDefaultBranch);

    expect($candidate->decision)->not->toBe(PublicationDecision::Publish);
})->with([
    'not on main history' => [true, true, false],
    'gate did not pass' => [true, false, true],
    'not a release merge' => [false, true, true],
]);

it('refuses to reconcile a commit that did not merge a release pull request', function () {
    $candidate = PublicationCandidate::select('workflow_dispatch', '', RELEASE_SHA)
        ->verify(releaseCommit: false, gatePassed: true, onDefaultBranch: true);

    expect($candidate->decision)->toBe(PublicationDecision::Refuse);
});

it('quietly ignores an automatic run for an ordinary main commit', function () {
    $candidate = PublicationCandidate::select('workflow_run', RELEASE_SHA, '')
        ->verify(releaseCommit: false, gatePassed: true, onDefaultBranch: true);

    expect($candidate->decision)->toBe(PublicationDecision::Nothing);
});

it('creates a missing tag and release at the verified sha', function () {
    $publication = TagPublication::decide('v2.2.0', RELEASE_SHA, '', ReleaseState::Missing);

    expect($publication->blocked())->toBeFalse()
        ->and($publication->createTag)->toBeTrue()
        ->and($publication->createRelease)->toBeTrue()
        ->and($publication->publishDraft)->toBeFalse();
});

it('completes a missing release without rewriting its correct tag', function () {
    $publication = TagPublication::decide('v2.2.0', RELEASE_SHA, RELEASE_SHA, ReleaseState::Missing);

    expect($publication->blocked())->toBeFalse()
        ->and($publication->createTag)->toBeFalse()
        ->and($publication->createRelease)->toBeTrue();
});

it('publishes a draft without recreating its correct tag', function () {
    $publication = TagPublication::decide('v2.2.0', RELEASE_SHA, RELEASE_SHA, ReleaseState::Draft);

    expect($publication->createTag)->toBeFalse()
        ->and($publication->createRelease)->toBeFalse()
        ->and($publication->publishDraft)->toBeTrue();
});

it('is idempotent when the exact release is already published', function () {
    $publication = TagPublication::decide('v2.2.0', RELEASE_SHA, RELEASE_SHA, ReleaseState::Published);

    expect($publication->settled())->toBeTrue();
});

it('rejects a conflicting tag before any mutation is requested', function () {
    $publication = TagPublication::decide('v2.2.0', RELEASE_SHA, OTHER_SHA, ReleaseState::Missing);

    expect($publication->blocked())->toBeTrue()
        ->and($publication->createTag)->toBeFalse()
        ->and($publication->createRelease)->toBeFalse()
        ->and($publication->publishDraft)->toBeFalse();
});
