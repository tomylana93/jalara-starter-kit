<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use App\Support\Ci\GateOutcome;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Prints the gate verdict for the exact revision the required check reports on.
 *
 * The workflow gathers the runs that could carry the verdict and hands them
 * over as a document; the decision itself lives here so the test suite covers
 * it rather than a shell pipeline nobody runs until a title is edited.
 */
#[Signature('ci:gate-outcome {--payload= : Path to the gate outcome payload JSON}')]
#[Description('Resolve the gate outcome for a pull request revision.')]
final class CheckGateOutcome extends Command
{
    public function handle(): int
    {
        $payload = CiPayload::readFile((string) $this->option('payload'));

        $this->line(GateOutcome::resolve(
            CiPayload::string($payload, 'own', 'skipped'),
            CiPayload::string($payload, 'live'),
            CiPayload::string($payload, 'event'),
            CiPayload::maps($payload, 'runs'),
        ));

        return self::SUCCESS;
    }
}
