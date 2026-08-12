<?php

use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/*
 * The properties the automation is supposed to have, asserted against the files
 * themselves.
 *
 * A workflow is only ever exercised by running it, and the runs that matter most
 * — a fork's pull request, a release with no credential configured, a direct
 * push to the default branch — are the ones nobody wants to stage. These cases
 * cover the structural half of that: what each file triggers on, what it is
 * allowed to do, and which guard decides whether it does anything at all.
 */

/**
 * @return array<string, array<array-key, mixed>>
 */
function ciWorkflows(): array
{
    $workflows = [];

    foreach (glob(ciRepositoryRoot().'/.github/workflows/*.yml') ?: [] as $path) {
        $parsed = Yaml::parseFile($path);
        $workflows[basename($path)] = is_array($parsed) ? $parsed : [];
    }

    return $workflows;
}

function ciRepositoryRoot(): string
{
    return dirname(__DIR__, 3);
}

/**
 * YAML 1.1 reads a bare `on` as a boolean, so the trigger block can arrive under
 * either key depending on the parser.
 *
 * @param  array<array-key, mixed>  $workflow
 * @return array<array-key, mixed>
 */
function ciTriggers(array $workflow): array
{
    $triggers = $workflow['on'] ?? $workflow[true] ?? [];

    return is_array($triggers) ? $triggers : [];
}

/**
 * The first step whose `$key` contains `$needle`, or an empty array.
 *
 * @return array<array-key, mixed>
 */
function ciStep(mixed $steps, string $key, string $needle): array
{
    foreach (is_array($steps) ? $steps : [] as $step) {
        if (is_array($step) && Str::contains((string) ($step[$key] ?? ''), $needle)) {
            return $step;
        }
    }

    return [];
}

/**
 * @return list<string>
 */
function ciAutomationFiles(): array
{
    $files = array_merge(
        glob(ciRepositoryRoot().'/.github/workflows/*.yml') ?: [],
        glob(ciRepositoryRoot().'/.github/actions/*/action.yml') ?: [],
        glob(ciRepositoryRoot().'/.github/scripts/*.sh') ?: [],
    );

    sort($files);

    return $files;
}

it('parses every workflow', function () {
    expect(array_keys(ciWorkflows()))->toContain(
        '_ci.yml',
        'pull-request.yml',
        'main.yml',
        'release-pr.yml',
        'release-publish.yml',
        'installer-smoke.yml',
        'dependency-audit.yml',
    );
});

/*
 * A moving tag is a remote code execution primitive with extra steps: whoever
 * can move it can run their code with this repository's token.
 */
it('pins every third-party action to a full commit sha', function () {
    $unpinned = [];

    foreach (ciAutomationFiles() as $path) {
        if (! Str::endsWith($path, '.yml')) {
            continue;
        }

        $references = Str::matchAll('/^\s*(?:- )?uses:\s*(\S+)/m', (string) file_get_contents($path));

        foreach ($references as $reference) {
            if (Str::startsWith($reference, './')) {
                continue;
            }

            if (! Str::isMatch('/@[0-9a-f]{40}$/', $reference)) {
                $unpinned[] = basename($path).': '.$reference;
            }
        }
    }

    expect($unpinned)->toBe([]);
});

/*
 * `pull_request_target` runs with the base repository's secrets. Checking out a
 * contribution under it hands a fork write access to this repository.
 */
it('never runs a contribution with the repository credentials', function () {
    foreach (ciAutomationFiles() as $path) {
        expect((string) file_get_contents($path))->not->toContain('pull_request_target');
    }
});

/*
 * The automation ships to every descendant of this starter kit. A repository
 * name baked into a workflow makes a downstream copy either silently inert or
 * quietly pointed at somebody else's repository.
 */
it('carries no hardcoded repository identity', function () {
    foreach (ciAutomationFiles() as $path) {
        expect((string) file_get_contents($path))
            ->not->toContain('tomylana93')
            ->not->toContain('jalara-starter-kit');
    }
});

/*
 * A token is only as narrow as the widest job that inherits it. Granting
 * nothing at the top of the file and asking per job means a job added later
 * inherits no access by accident.
 */
it('grants no permission by default and asks for one per job', function () {
    foreach (ciWorkflows() as $workflow) {
        expect($workflow['permissions'])->toBe([])
            ->and($workflow['jobs'])->each->toHaveKey('permissions');
    }
});

it('runs the full gate from one reusable implementation', function () {
    $workflows = ciWorkflows();

    expect(ciTriggers($workflows['_ci.yml']))->toHaveKey('workflow_call')
        ->and($workflows['pull-request.yml']['jobs']['gate']['uses'])->toBe('./.github/workflows/_ci.yml')
        ->and($workflows['main.yml']['jobs']['gate']['uses'])->toBe('./.github/workflows/_ci.yml');
});

it('keeps the expensive checks behind the cheap ones', function () {
    $jobs = ciWorkflows()['_ci.yml']['jobs'];

    expect($jobs['e2e']['needs'])->toBe(['static', 'vitest', 'pest', 'audit'])
        ->and($jobs['installer']['needs'])->toBe(['static', 'vitest', 'pest', 'audit']);
});

it('runs the gate for a ready pull request and not for a draft', function () {
    $workflow = ciWorkflows()['pull-request.yml'];

    expect($workflow['jobs']['gate']['if'])->toContain('draft == false')
        ->and($workflow['concurrency']['cancel-in-progress'])->toBeTrue();
});

it('cancels a superseded pull request run and keeps release runs queued', function () {
    $workflows = ciWorkflows();

    expect($workflows['main.yml']['concurrency']['cancel-in-progress'])->toBeFalse()
        ->and($workflows['release-pr.yml']['concurrency']['cancel-in-progress'])->toBeFalse()
        ->and($workflows['release-publish.yml']['concurrency']['cancel-in-progress'])->toBeFalse();
});

it('aggregates the pull request gates into one required check', function () {
    $jobs = ciWorkflows()['pull-request.yml']['jobs'];

    expect($jobs['gates']['needs'])->toBe(['workflows', 'gate', 'release-candidate', 'release-metadata'])
        ->and($jobs['required']['name'])->toBe('required')
        ->and($jobs['required']['needs'])->toBe('gates');
});

/*
 * An approval, a dismissal and a retitle all change whether a revision may be
 * merged without changing a line of it. The aggregate has to be reported again
 * for those events, and it has to re-read the policy rather than reuse a verdict
 * formed before they happened — otherwise the one check anybody reads can be
 * stale in either direction.
 */
it('re-reports the required check on every event and re-reads the policy live', function () {
    $required = ciWorkflows()['pull-request.yml']['jobs']['required'];

    expect($required['if'])->toContain('always()')
        ->and($required['if'])->not->toContain('github.event_name')
        ->and(ciStep($required['steps'], 'id', 'policy'))->not->toBe([])
        ->and(ciStep($required['steps'], 'id', 'outcome'))->not->toBe([]);
});

it('bootstraps pull request policy only from an internal branch', function () {
    $workflow = ciWorkflows()['pull-request.yml'];

    foreach (['policy', 'required'] as $jobName) {
        $steps = $workflow['jobs'][$jobName]['steps'];
        $selection = ciStep($steps, 'id', 'policy_source');
        $bootstrap = ciStep($steps, 'name', 'Bootstrap the policy from the internal branch');

        expect($selection)->not->toBe([])
            ->and($selection['run'])->toContain('HEAD_REPOSITORY', 'BASE_REPOSITORY', 'exit 1')
            ->and($bootstrap)->not->toBe([])
            ->and($bootstrap['if'])->toContain("steps.policy_source.outputs.use_head == 'true'")
            ->and($bootstrap['with']['ref'])->toBe('${{ github.event.pull_request.head.sha }}');
    }
});

it('re-runs the full gate on the commit that actually landed', function () {
    $triggers = ciTriggers(ciWorkflows()['main.yml']);

    expect($triggers['push']['branches'])->toBe(['main'])
        ->and($triggers)->not->toHaveKey('pull_request');
});

it('decides release eligibility on the default branch', function () {
    $jobs = ciWorkflows()['main.yml']['jobs'];

    expect($jobs['release-readiness']['needs'])->toBe('gate')
        ->and($jobs['required']['needs'])->toBe(['gate', 'release-readiness']);
});

/*
 * Release Please only proposes release metadata. The publisher creates the tag
 * at the already-verified sha itself, so it cannot also open a pull request or
 * let a moving target branch decide which commit is tagged.
 */
it('separates proposing a release from publishing one', function () {
    $workflows = ciWorkflows();

    $creator = ciStep($workflows['release-pr.yml']['jobs']['create']['steps'], 'uses', 'release-please-action');
    $publisher = $workflows['release-publish.yml']['jobs']['publish'];

    expect($creator['with']['skip-github-release'])->toBeTrue()
        ->and($creator['with'])->not->toHaveKey('skip-github-pull-request')
        ->and(ciStep($publisher['steps'], 'uses', 'release-please-action'))->toBe([])
        ->and(ciStep($publisher['steps'], 'name', 'Tag the verified commit'))->not->toBe([]);
});

it('uses the current GitHub App client id contract', function () {
    $action = (string) file_get_contents(
        ciRepositoryRoot().'/.github/actions/release-credentials/action.yml',
    );

    expect($action)->toContain('client-id:', 'inputs.client-id')
        ->and($action)->not->toContain('app-id:', 'inputs.app-id');

    foreach (['release-pr.yml', 'release-publish.yml'] as $name) {
        $workflow = (string) file_get_contents(ciRepositoryRoot().'/.github/workflows/'.$name);

        expect($workflow)->toContain('RELEASE_APP_CLIENT_ID')
            ->and($workflow)->not->toContain('RELEASE_APP_ID');
    }
});

it('releases only after a successful gate on the default branch', function () {
    foreach (['release-pr.yml', 'release-publish.yml'] as $name) {
        $workflow = ciWorkflows()[$name];
        $triggers = ciTriggers($workflow);
        $job = reset($workflow['jobs']);

        expect($triggers['workflow_run']['workflows'])->toBe(['main'])
            ->and($triggers['workflow_run']['branches'])->toBe(['main'])
            ->and($job['if'])->toContain("workflow_run.conclusion == 'success'");
    }
});

it('leaves release automation and the remote installer check switched off by default', function () {
    $workflows = ciWorkflows();

    expect($workflows['installer-smoke.yml']['jobs']['smoke']['if'])
        ->toBe("vars.STARTER_KIT_MODE == 'true'");

    foreach (['release-pr.yml', 'release-publish.yml'] as $name) {
        $capability = ciStep(reset($workflows[$name]['jobs'])['steps'], 'id', 'capability');

        expect($capability)->not->toBe([])
            ->and($capability['env'])->toHaveKey('RELEASE_ENABLED');
    }
});

it('ships every script the workflows call', function () {
    $scripts = [
        'audit-dependencies.sh',
        'provenance-report.sh',
        'pull-request-reviews.sh',
        'release-commit.sh',
    ];

    foreach ($scripts as $script) {
        $path = ciRepositoryRoot().'/.github/scripts/'.$script;

        expect($path)->toBeFile()
            ->and(is_executable($path))->toBeTrue("{$script} is not executable");
    }
});

it('keeps the remediation ledger versioned in the repository', function () {
    $ledger = json_decode(
        (string) file_get_contents(ciRepositoryRoot().'/.github/release-provenance.json'),
        true,
    );

    expect($ledger)->toHaveKeys(['baseline', 'remediated'])
        ->and($ledger['baseline'])->toMatch('/^[0-9a-f]{40}$/')
        ->and($ledger['remediated'])->toBeArray();
});
