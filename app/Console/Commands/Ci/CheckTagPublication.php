<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use App\Support\Ci\ReleaseState;
use App\Support\Ci\TagPublication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Decides what publishing a version still has to do, before anything is
 * written.
 *
 * Nothing here mutates: it reads the state the workflow gathered and answers
 * with the steps left. Deciding first is the point — a tag that already names a
 * different commit has to stop the run while stopping is still free, rather
 * than be discovered after the ref was written.
 */
#[Signature('ci:tag-publication {--payload= : Path to the publication state JSON} {--output= : Path to append the workflow outputs to}')]
#[Description('Decide what remains to publish a release tag and its GitHub Release.')]
final class CheckTagPublication extends Command
{
    public function handle(): int
    {
        $payload = CiPayload::readFile((string) $this->option('payload'));

        $publication = TagPublication::decide(
            CiPayload::string($payload, 'tag'),
            CiPayload::string($payload, 'candidate_sha'),
            CiPayload::string($payload, 'tagged_sha'),
            ReleaseState::fromLookup(CiPayload::string($payload, 'release', 'missing')),
        );

        $output = (string) $this->option('output');

        if ($output !== '') {
            file_put_contents($output, sprintf(
                "create_tag=%s\ncreate_release=%s\npublish_draft=%s\n",
                $publication->createTag ? 'true' : 'false',
                $publication->createRelease ? 'true' : 'false',
                $publication->publishDraft ? 'true' : 'false',
            ), FILE_APPEND);
        }

        if ($publication->blocked()) {
            $this->line("::error::{$publication->conflict}");

            return self::FAILURE;
        }

        $this->line($publication->summary);

        return self::SUCCESS;
    }
}
