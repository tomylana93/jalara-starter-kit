<?php

namespace App\Http\Controllers\Backups;

use App\Actions\Backups\StartBackupRun;
use App\Actions\Backups\StartRestoreRun;
use App\Actions\Backups\UploadBackup;
use App\Http\Controllers\Controller;
use App\Http\Presenters\BackupPresenter;
use App\Http\Requests\Backups\UploadBackupRequest;
use App\Models\BackupRun;
use App\Settings\GeneralSettings;
use App\Support\Backups\BackupArchive;
use App\Support\Backups\BackupArchives;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
     * Take in an archive produced elsewhere.
     *
     * The request has already established that the file is an archive of this
     * application, entry by entry. Nothing here trusts the client's filename
     * beyond using it as a label.
     */
    public function upload(UploadBackupRequest $request, UploadBackup $uploadBackup): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('archive');

        $uploadBackup->handle($file);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.message.uploaded')]);

        return to_route('settings.backups.index');
    }

    /**
     * Queue a restore of one archive.
     *
     * Queued rather than performed here for the same reason a backup is, and
     * more so: this unpacks an archive, replaces the database and copies media
     * back, which no request timeout should be deciding the outcome of. The
     * page's poll then reports it like any other run.
     *
     * The filename is resolved against the real listing, exactly as `download`
     * does, so a value naming anything else simply matches nothing.
     */
    public function restore(string $filename, Request $request, BackupArchives $archives, StartRestoreRun $startRestoreRun): RedirectResponse
    {
        $archive = $archives->find($filename);

        abort_if(! $archive instanceof BackupArchive, 404);

        $run = $startRestoreRun->handle($archive, $request->user());

        if (! $run instanceof BackupRun) {
            /* The lock is held by a backup or another restore. */
            Inertia::flash('toast', ['type' => 'error', 'message' => __('backup.message.already_running')]);

            return to_route('settings.backups.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('backup.message.restore_started')]);

        return to_route('settings.backups.index');
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
