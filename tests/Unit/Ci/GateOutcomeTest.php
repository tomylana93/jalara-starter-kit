<?php

use App\Support\Ci\GateOutcome;

/*
 * `CI / required` reports on events that run no gate job at all — an approval, a
 * dismissal, a retitled pull request — so the gate half of its verdict is looked
 * up rather than produced. These are the races that lookup has to survive, and
 * none of them can be staged on a live repository without deliberately breaking
 * a pull request in front of everybody.
 */

/**
 * One entry of the document the workflow hands over: a `pull_request` run for
 * the head sha, with its `gates` job attached.
 *
 * @param  array<string, mixed>  $gates
 * @return array<string, mixed>
 */
function ciRun(string $status, array $gates): array
{
    return ['status' => $status, 'gates' => $gates];
}

/**
 * A run that concluded a gate verdict.
 *
 * @return array<string, mixed>
 */
function ciCompletedRun(string $conclusion): array
{
    return ciRun('completed', ['status' => 'completed', 'conclusion' => $conclusion]);
}

/**
 * A run whose gate is still going.
 *
 * @return array<string, mixed>
 */
function ciRunningRun(): array
{
    return ciRun('in_progress', ['status' => 'in_progress', 'conclusion' => null]);
}

/**
 * A run that decided nothing about the gate: an `edited` run skips the gate
 * jobs, and a cancelled one abandoned them.
 *
 * @return array<string, mixed>
 */
function ciMetadataRun(string $conclusion = 'skipped'): array
{
    return ciRun('completed', ['status' => 'completed', 'conclusion' => $conclusion]);
}

const CI_HEAD = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const CI_OLD_HEAD = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

it('answers with its own verdict when this run produced one', function () {
    expect(GateOutcome::resolve('success', CI_HEAD, CI_HEAD, []))->toBe('success')
        ->and(GateOutcome::resolve('failure', CI_HEAD, CI_HEAD, []))->toBe('failure');
});

/*
 * The defect this replaces: an `edited` run looked itself up, found itself in
 * flight, and reported `pending`. Nothing ran afterwards to correct it, so a
 * corrected title left the only check anybody reads permanently red.
 */
it('does not find itself in flight when the event ran no gate', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciCompletedRun('success'),
    ]))->toBe('success');
});

it('reads a retitled pull request as passing when the gate passed for that revision', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciMetadataRun(),
        ciCompletedRun('success'),
    ]))->toBe('success');
});

it('reads a retitled pull request as failing when the gate failed for that revision', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciCompletedRun('failure'),
    ]))->toBe('failure');
});

it('looks past a run that decided nothing about the gate', function (string $conclusion) {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciMetadataRun($conclusion),
        ciCompletedRun('success'),
    ]))->toBe('success');
})->with(['skipped', 'cancelled']);

it('waits rather than guessing while the gate for this revision is still running', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciRunningRun(),
        ciCompletedRun('failure'),
    ]))->toBe(GateOutcome::PENDING);
});

it('reports no gate at all when nothing produced one for the revision', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, []))->toBe(GateOutcome::NONE)
        ->and(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [ciMetadataRun()]))->toBe(GateOutcome::NONE);
});

/*
 * The other race: a gate that was already running when a new revision was
 * pushed finishes afterwards. Its verdict describes the revision it started on,
 * not the one being reported for, so it must never be reused as this run's own
 * answer.
 */
it('refuses its own verdict once the branch moved past the revision it judged', function () {
    expect(GateOutcome::resolve('success', CI_HEAD, CI_OLD_HEAD, []))->toBe(GateOutcome::NONE);
});

it('answers for the live revision rather than the one it was triggered with', function () {
    expect(GateOutcome::resolve('success', CI_HEAD, CI_OLD_HEAD, [
        ciCompletedRun('failure'),
    ]))->toBe('failure');
});

it('treats a gate job that concluded nothing as no verdict', function () {
    expect(GateOutcome::resolve('skipped', CI_HEAD, CI_HEAD, [
        ciRun('completed', ['status' => 'completed', 'conclusion' => null]),
    ]))->toBe(GateOutcome::NONE);
});
