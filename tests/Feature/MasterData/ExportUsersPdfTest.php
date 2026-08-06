<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Enums\DateFormat;
use App\Enums\Role;
use App\Http\Requests\MasterData\ExportUsersRequest;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\Documents\DocumentStylesheet;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    app(SyncAuthorization::class)->handle();

    /*
     * The document inlines the built print bundle, and the Pest job carries no
     * Node and builds no assets on purpose. Binding the stylesheet keeps that
     * dependency out of the assertions without softening it in production.
     */
    app()->instance(DocumentStylesheet::class, new class extends DocumentStylesheet
    {
        public function contents(): string
        {
            return '/* built stylesheet */';
        }
    });

    /*
     * Rendering is faked throughout: Chromium is only present in the end to end
     * job, which owns the single test that proves a real PDF comes out. What is
     * worth asserting here is the document itself, and the fake exposes it.
     */
    Pdf::fake();
});

/**
 * A user allowed to browse users.
 */
function pdfViewer(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('renders the selected users into the document', function () {
    $viewer = pdfViewer();
    $ada = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$ada->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        return $pdf->downloadName === 'users.pdf'
            && str_contains($pdf->getHtml(), 'Ada Lovelace')
            && str_contains($pdf->getHtml(), 'ada@example.com');
    });
});

it('keeps the rows in the order they were selected', function () {
    $viewer = pdfViewer();
    $first = User::factory()->create(['name' => 'Ada Lovelace']);
    $second = User::factory()->create(['name' => 'Grace Hopper']);

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$second->id, $first->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => strpos($pdf->getHtml(), 'Grace Hopper') < strpos($pdf->getHtml(), 'Ada Lovelace'));
});

/*
 * Chromium renders on the server, so without the reader's zone travelling with
 * the request the document would silently disagree with the screen it came from.
 */
it('stamps the timestamps in the reader timezone rather than the server one', function () {
    $viewer = pdfViewer();
    $user = User::factory()->create(['created_at' => '2026-07-30 22:30:00']);

    $settings = app(GeneralSettings::class);
    $settings->dateFormat = DateFormat::DayMonthYearSlashed;
    $settings->save();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', [
            'ids' => [$user->id],
            'timezone' => 'Asia/Jakarta',
        ]))
        ->assertOk();

    /* 22:30 UTC on the 30th is 05:30 on the 31st in Jakarta. */
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => str_contains($pdf->getHtml(), '31/07/2026 05:30'));
});

it('falls back to UTC when no timezone is offered', function () {
    $viewer = pdfViewer();
    $user = User::factory()->create(['created_at' => '2026-07-30 22:30:00']);

    $settings = app(GeneralSettings::class);
    $settings->dateFormat = DateFormat::DayMonthYearSlashed;
    $settings->save();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$user->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => str_contains($pdf->getHtml(), '30/07/2026 22:30'));
});

it('rejects a timezone that names no real zone', function () {
    $viewer = pdfViewer();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', [
            'ids' => [$viewer->id],
            'timezone' => 'Mars/Olympus_Mons',
        ]))
        ->assertSessionHasErrors('timezone');
});

it('names who generated the document and when', function () {
    $viewer = pdfViewer();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$viewer->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($viewer): bool {
        return str_contains($pdf->getHtml(), $viewer->name)
            && str_contains($pdf->getHtml(), __('master_data.user.document.generated_by'));
    });
});

it('carries no credential material into the document', function () {
    $viewer = pdfViewer();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$viewer->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($viewer): bool {
        return ! str_contains($pdf->getHtml(), (string) $viewer->password)
            && ! str_contains($pdf->getHtml(), 'password');
    });
});

it('inlines the print stylesheet rather than linking it', function () {
    $viewer = pdfViewer();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [$viewer->id]]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        return str_contains($pdf->getHtml(), '/* built stylesheet */')
            && ! str_contains($pdf->getHtml(), '<link rel="stylesheet"');
    });
});

it('applies the same selection ceiling the spreadsheet applies', function () {
    $viewer = pdfViewer();
    $ids = User::factory()->count(ExportUsersRequest::MAX_IDS)->create()->pluck('id')->all();

    actingAs($viewer)
        ->get(route('master-data.users.export.pdf', ['ids' => [...$ids, $viewer->id]]))
        ->assertSessionHasErrors('ids');
});

it('requires a selection at all', function () {
    actingAs(pdfViewer())
        ->get(route('master-data.users.export.pdf'))
        ->assertSessionHasErrors('ids');
});

it('refuses an actor who may not browse users', function () {
    $outsider = User::factory()->create();

    actingAs($outsider)
        ->get(route('master-data.users.export.pdf', ['ids' => [$outsider->id]]))
        ->assertForbidden();
});

it('keeps the document out of reach of a guest', function () {
    $user = User::factory()->create();

    get(route('master-data.users.export.pdf', ['ids' => [$user->id]]))
        ->assertRedirect(route('login'));
});
