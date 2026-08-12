<?php

use Illuminate\Support\Facades\File;

/*
 * The merge policy is the only enforcement this repository has: a private
 * repository on the GitHub Free plan cannot protect a branch or require a
 * check. These cases pin the decision the command makes for each shape of pull
 * request the workflow can hand it.
 *
 * A branch inside the repository can only be pushed by an account with write
 * access, so trust follows the head repository rather than the author's badge.
 * For the same reason an approval is judged by the reviewer's resolved
 * repository permission and never by `author_association`, which says who
 * somebody is rather than what they may do.
 */

afterEach(function (): void {
    File::deleteDirectory(sys_get_temp_dir().'/jalara-ci-policy');
});

/**
 * @param  array<array-key, mixed>  $contents
 */
function ciPolicyFixture(string $name, array $contents): string
{
    $directory = sys_get_temp_dir().'/jalara-ci-policy';
    File::ensureDirectoryExists($directory);

    $path = "{$directory}/{$name}";
    File::put($path, (string) json_encode($contents));

    return $path;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function ciPullRequestEvent(array $overrides = []): string
{
    return ciPolicyFixture('event.json', [
        'pull_request' => array_merge([
            'number' => 42,
            'title' => 'feat(ci): add a trunk based gate',
            'draft' => false,
            'head' => [
                'sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                'repo' => ['full_name' => 'acme/app'],
            ],
            'base' => [
                'repo' => ['full_name' => 'acme/app'],
            ],
        ], $overrides),
    ]);
}

/**
 * @param  list<array<string, mixed>>  $reviews
 */
function ciReviews(array $reviews): string
{
    return ciPolicyFixture('reviews.json', $reviews);
}

/**
 * The fork of a contributor who has no write access here.
 *
 * @return array<string, mixed>
 */
function ciForkHead(string $sha = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'): array
{
    return [
        'sha' => $sha,
        'repo' => ['full_name' => 'contributor/app'],
    ];
}

it('accepts an internal maintainer pull request without any review', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(),
    ]))->assertSuccessful();
});

it('rejects a pull request whose title is not a Conventional Commit', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(['title' => 'Add a trunk based gate']),
    ]))->assertFailed();
});

it('rejects a pull request from a fork that carries no approval', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(['head' => ciForkHead()]),
        '--reviews' => ciReviews([]),
    ]))->assertFailed();
});

it('accepts a pull request from a fork approved by an account with write access', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(['head' => ciForkHead()]),
        '--reviews' => ciReviews([[
            'state' => 'APPROVED',
            'commit_id' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'permission' => 'write',
        ]]),
    ]))->assertSuccessful();
});

it('drops an approval once the fork pushes a new revision', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent([
            'head' => ciForkHead('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'),
        ]),
        '--reviews' => ciReviews([[
            'state' => 'APPROVED',
            'commit_id' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'permission' => 'admin',
        ]]),
    ]))->assertFailed();
});

/*
 * `COLLABORATOR` is the association GitHub reports for a read-only invitation
 * as well as for a maintainer, which is exactly why the permission decides.
 */
it('ignores an approval from a collaborator who only has read access', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(['head' => ciForkHead()]),
        '--reviews' => ciReviews([[
            'state' => 'APPROVED',
            'commit_id' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'author_association' => 'COLLABORATOR',
            'permission' => 'read',
        ]]),
    ]))->assertFailed();
});

it('refuses an approval whose permission could not be resolved', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent(['head' => ciForkHead()]),
        '--reviews' => ciReviews([[
            'state' => 'APPROVED',
            'commit_id' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'permission' => 'unknown',
        ]]),
    ]))->assertFailed();
});

it('treats a pull request whose fork was deleted as untrusted', function () {
    pendingCommand($this->artisan('ci:pull-request-policy', [
        '--event' => ciPullRequestEvent([
            'head' => ['sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]),
        '--reviews' => ciReviews([]),
    ]))->assertFailed();
});
