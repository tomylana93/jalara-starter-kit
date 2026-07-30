<?php

namespace App\Http\Controllers\MasterData;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\ExportUsersRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportUsersController extends Controller
{
    /**
     * Download the selected users as a spreadsheet.
     *
     * The filename stays ASCII so no client has to negotiate an encoded one.
     */
    public function __invoke(ExportUsersRequest $request, UsersExport $usersExport): BinaryFileResponse
    {
        return response()
            ->download($usersExport->write($request->selectedIds()), 'users.xlsx')
            ->deleteFileAfterSend();
    }
}
