<?php

namespace App\Support\Ci;

/**
 * Resolves what the gates concluded about the exact revision the required
 * check is reporting for.
 *
 * The required check runs on events that change no code — an approval, a
 * dismissal, a retitle — and those runs never execute the gate jobs. The
 * answer then comes from the runs that did: the completed or still-running
 * `pull_request` runs for the same head sha, newest first. A run whose `gates`
 * job produced no verdict (an edited-title run, a cancelled run) is
 * metadata-only and skipped, and the run asking the question is never its own
 * answer.
 */
final class GateOutcome
{
    /**
     * The gate verdict is still being produced by a run in flight.
     */
    public const string PENDING = 'pending';

    /**
     * No gate-producing run exists for the revision at all.
     */
    public const string NONE = 'none';

    /**
     * A `gates` job conclusion that carries no verdict.
     *
     * @var list<string>
     */
    private const array VERDICTLESS = ['skipped', 'cancelled'];

    /**
     * @param  string  $ownResult  The `gates` result of the run asking, or `skipped` when this event did not run the gates.
     * @param  string  $liveHeadSha  The head sha read from GitHub at report time.
     * @param  string  $eventHeadSha  The head sha the run was triggered with.
     * @param  list<array<array-key, mixed>>  $runs  `pull_request` runs for the live head sha, newest first, current run excluded, each with its `gates` job attached.
     */
    public static function resolve(string $ownResult, string $liveHeadSha, string $eventHeadSha, array $runs): string
    {
        // This run produced the verdict itself, and the revision it judged is
        // still the revision being reported for.
        if ($ownResult !== 'skipped' && $liveHeadSha === $eventHeadSha) {
            return $ownResult;
        }

        foreach ($runs as $run) {
            $gates = CiPayload::map($run, 'gates');
            $conclusion = CiPayload::string($gates, 'conclusion');
            // A metadata-only run never produced a gate verdict, so it is
            // transparent to the lookup rather than an answer.
            if ($gates === []) {
                continue;
            }

            if (in_array($conclusion, self::VERDICTLESS, true)) {
                continue;
            }

            if (CiPayload::string($run, 'status') !== 'completed') {
                return self::PENDING;
            }

            return $conclusion === '' ? self::NONE : $conclusion;
        }

        return self::NONE;
    }
}
