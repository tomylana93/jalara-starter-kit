<?php

namespace App\Http\Controllers\MasterData;

use App\Exports\UsersPdfExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\ExportUsersRequest;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class ExportUsersPdfController extends Controller
{
    /**
     * Download the selected users as a printable document.
     *
     * A4 is set explicitly because the package defaults to Letter, which would
     * quietly clip a document laid out for the paper everyone here actually
     * prints on. The filename stays ASCII so no client has to negotiate an
     * encoded one.
     */
    public function __invoke(ExportUsersRequest $request, UsersPdfExport $usersPdfExport): PdfBuilder
    {
        return Pdf::view('pdf.users', $usersPdfExport->viewData(
            $request->selectedIds(),
            $request->timeZone(),
            $request->actor(),
        ))
            ->format(Format::A4)
            ->margins(12, 12, 16, 12)
            ->footerView('pdf.footer')
            ->download('users.pdf');
    }
}
