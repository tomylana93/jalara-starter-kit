<?php

namespace App\Http\Controllers\MasterData;

use App\Authorization\AuthorizationCatalog;
use App\Http\Controllers\Controller;
use App\Imports\UserImportTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserImportTemplateController extends Controller
{
    /**
     * Download the spreadsheet an import is expected to be shaped like.
     *
     * The filename stays ASCII so no client has to negotiate an encoded one.
     */
    public function __invoke(AuthorizationCatalog $catalog, UserImportTemplate $template): BinaryFileResponse
    {
        Gate::authorize('create', User::class);

        return response()
            ->download($template->write($catalog->assignableRoleValues()), 'users-import-template.xlsx')
            ->deleteFileAfterSend();
    }
}
