<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use App\Support\Ci\PublicationCandidate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Decides which commit the publisher may tag, and says so on the job's outputs.
 *
 * The workflow gathers the facts — whether the commit merged a release pull
 * request, whether its own `main` run passed, whether it is on the default
 * branch — and this command judges them. Keeping the judgement here is what
 * lets the test suite reproduce the races that decide whether a release is
 * published at all, none of which can be staged safely on a live repository.
 */
#[Signature('ci:publication-candidate {--payload= : Path to the candidate payload JSON} {--output= : Path to append the workflow outputs to}')]
#[Description('Decide which commit, if any, the release publisher may tag.')]
final class CheckPublicationCandidate extends Command
{
    public function handle(): int
    {
        $payload = CiPayload::readFile((string) $this->option('payload'));

        $candidate = PublicationCandidate::select(
            CiPayload::string($payload, 'event'),
            CiPayload::string($payload, 'run_sha'),
            CiPayload::string($payload, 'requested_sha'),
        )->verify(
            CiPayload::boolean($payload, 'release_commit'),
            CiPayload::boolean($payload, 'gate_passed'),
            CiPayload::boolean($payload, 'on_default_branch'),
        );

        $output = (string) $this->option('output');

        if ($output !== '') {
            file_put_contents($output, sprintf(
                "proceed=%s\nsha=%s\n",
                $candidate->decision->proceeds() ? 'true' : 'false',
                $candidate->sha,
            ), FILE_APPEND);
        }

        if ($candidate->decision->fails()) {
            $this->line("::error::{$candidate->reason}");

            return self::FAILURE;
        }

        $this->line($candidate->reason);

        return self::SUCCESS;
    }
}
