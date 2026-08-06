<?php

namespace App\Http\Controllers\MasterData;

use App\Authorization\AuthorizationCatalog;
use App\Exceptions\DefaultUserPasswordNotConfigured;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\ImportUsersRequest;
use App\Imports\UsersImport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ImportUsersController extends Controller
{
    /**
     * Create users from an uploaded spreadsheet.
     *
     * A sheet with any invalid row throws out of the importer as a validation
     * failure, which lands back on the table with one message per offending
     * row and nothing written.
     */
    public function __invoke(
        ImportUsersRequest $request,
        UsersImport $usersImport,
        AuthorizationCatalog $catalog,
    ): RedirectResponse {
        if (! $request->hasDefaultPassword()) {
            return $this->reportMissingPassword(__('master_data.user.import.message.password_missing'));
        }

        try {
            $created = $usersImport->handle($request->sheetPath(), $catalog->assignableRoleValues());
        } catch (DefaultUserPasswordNotConfigured $defaultUserPasswordNotConfigured) {
            /* The setting was cleared between the page render and this request. */
            return $this->reportMissingPassword($defaultUserPasswordNotConfigured->getMessage());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('master_data.user.import.message.imported', ['count' => $created]),
        ]);

        return to_route('master-data.users.index');
    }

    /**
     * Report that provisioning cannot run without a default password.
     */
    private function reportMissingPassword(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back();
    }
}
