<?php

namespace App\Http\Controllers\Backups;

use App\Actions\Backups\StartBackupRun;
use App\Http\Controllers\Controller;
use App\Http\Presenters\BackupPresenter;
use App\Models\BackupRun;
use App\Settings\GeneralSettings;
use App\Support\Backups\BackupArchive;
use App\Support\Backups\BackupArchives;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * How much run history the page shows. Older rows stay in the table until
     * pruning removes them; the page is a status surface, not an audit log.
     */
    private const int RUN_HISTORY_LIMIT = 10;

    /**
     * Show the backup page.
     */
    public function index(BackupArchives $archives, GeneralSettings $generalSettings): Response
    {
        return Inertia::render('settings/Backups', [
            /* Timestamps travel as UTC ISO 8601; the browser applies this. */
            'dateFormat' => $generalSettings->dateFormat->value,
            'archives' => BackupPresenter::presentArchives($archives->all()),
            'runs' => BackupPresenter::presentRuns(
                BackupRun::query()
                    ->with('user')
                    ->latest('created_at')
                    ->orderByDesc('id')
                    ->limit(self::RUN_HISTORY_LIMIT)
                    ->get(),
            ),
            /*
             * Drives the page's polling: it reloads only while this is set, so an
             * idle page issues no requests at all.
             */
            'activeRun' => ($activeRun = BackupRun::query()->active()->latest('created_at')->first()) instanceof BackupRun
                ? BackupPresenter::presentRun($activeRun)
                : null,
        ]);
    }

    /**
     * Start a backup now.
     */
    public function store(Request $request, StartBackupRun $startBackupRun): RedirectResponse
    {
        $run = $startBackupRun->handle($request->user());

        if (! $run instanceof BackupRun) {
            /*
             * Someone else - or the schedule - already holds the lock. This is a
             * normal outcome, not an error state, so the page simply says so.
             */
            Inertia::flash('toast', ['type' => 'error', 'message' => __('backup.message.already_running')]);

            return to_route('settings.backups.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.message.started')]);

        return to_route('settings.backups.index');
    }

    /**
     * Stream one archive.
     *
     * The filename is resolved against the real listing rather than joined onto
     * a root, so a value naming anything else simply matches nothing. The
     * response streams: an archive may be gigabytes and must not be read into
     * memory.
     */
    public function download(string $filename, BackupArchives $archives): StreamedResponse
    {
        $archive = $archives->find($filename);

        abort_if(! $archive instanceof BackupArchive, 404);

        return Storage::disk($archive->diskName)
            ->download($archive->path, $archive->filename);
    }

    /**
     * Delete one archive.
     */
    public function destroy(string $filename, BackupArchives $archives): RedirectResponse
    {
        $archive = $archives->find($filename);

        abort_if(! $archive instanceof BackupArchive, 404);

        $archives->delete($archive);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.message.deleted')]);

        return to_route('settings.backups.index');
    }
}
