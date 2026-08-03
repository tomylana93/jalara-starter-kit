<?php

namespace App\Console\Commands;

use App\Actions\Media\PruneOrphanImages as PruneOrphanImagesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Reports, and on request deletes, stored images that nothing points at.
 *
 * The sweep itself lives in `App\Actions\Media\PruneOrphanImages`; this command
 * only turns command-line options into that call and renders what came back.
 */
#[Signature('images:prune-orphans {--delete : Delete the orphans instead of only reporting them} {--older-than=24 : Ignore files modified within this many hours}')]
#[Description('Report or delete stored images that nothing references.')]
class PruneOrphanImages extends Command
{
    public function handle(PruneOrphanImagesAction $pruneOrphanImages): int
    {
        $result = $pruneOrphanImages->handle(
            (int) $this->option('older-than'),
            (bool) $this->option('delete'),
        );

        $this->line(__('console.prune_orphan_images.mode.'.($result->dryRun ? 'dry_run' : 'delete'), [
            'hours' => $result->hours,
        ]));

        foreach ($result->disks as $diskName => $counts) {
            $this->line(__('console.prune_orphan_images.disk', [
                'disk' => $diskName,
                'candidates' => $counts['candidates'],
                'deleted' => $counts['deleted'],
                'skipped' => $counts['skipped'],
            ]));
        }

        return self::SUCCESS;
    }
}
