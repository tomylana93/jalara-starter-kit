<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Http\Presenters\DocumentationPresenter;
use App\Http\Requests\Documentation\IndexDocumentationManagementRequest;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Tables\DocumentationTable;
use App\Tables\TableQuery;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationManagementController extends Controller
{
    /**
     * Show the management overview: every category, one page of documents.
     *
     * Categories stay unpaginated because the sidebar, the editor select, and
     * the management dialogs all need the complete hierarchy.
     */
    public function index(IndexDocumentationManagementRequest $request): Response
    {
        Gate::authorize('create', Documentation::class);

        return Inertia::render('documentation/manage/Index', [
            'categories' => fn (): array => DocumentationPresenter::managementCategories(
                DocumentationCategory::query()->withCount('documentations')->orderBy('position')->get(),
            ),
            'documentations' => fn (): array => (new DocumentationTable)->paginate(new TableQuery(
                /* The hierarchy reads top down, so the fixed ordering ascends. */
                direction: 'asc',
                page: (int) ($request->validated('page') ?? 1),
            )),
        ]);
    }
}
