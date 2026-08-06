<?php

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\CreateDocumentation;
use App\Actions\Documentation\DeleteDocumentation;
use App\Actions\Documentation\MoveDocumentation;
use App\Actions\Documentation\UpdateDocumentation;
use App\Http\Controllers\Controller;
use App\Http\Presenters\DocumentationPresenter;
use App\Http\Requests\Documentation\StoreDocumentationRequest;
use App\Http\Requests\Documentation\UpdateDocumentationRequest;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationWriteController extends Controller
{
    /**
     * Show the editor with an empty document.
     */
    public function create(): Response
    {
        Gate::authorize('create', Documentation::class);

        return Inertia::render('documentation/manage/Edit', DocumentationPresenter::editorProps(
            DocumentationCategory::query()->orderBy('position')->get(),
        ));
    }

    /**
     * Store a new document and return the author to the management list.
     */
    public function store(StoreDocumentationRequest $request, CreateDocumentation $createDocumentation): RedirectResponse
    {
        $createDocumentation->handle($request->documentationAttributes());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.created'),
        ]);

        return to_route('documentation.manage.index');
    }

    /**
     * Show the editor loaded with an existing document.
     */
    public function edit(Documentation $documentation): Response
    {
        Gate::authorize('update', $documentation);

        return Inertia::render('documentation/manage/Edit', DocumentationPresenter::editorProps(
            DocumentationCategory::query()->orderBy('position')->get(),
            $documentation,
        ));
    }

    /**
     * Apply the editor changes and return the author to the management list.
     */
    public function update(UpdateDocumentationRequest $request, Documentation $documentation, UpdateDocumentation $updateDocumentation): RedirectResponse
    {
        $updateDocumentation->handle($documentation, $request->documentationAttributes());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.updated'),
        ]);

        return to_route('documentation.manage.index');
    }

    /**
     * Permanently delete a document.
     */
    public function destroy(Documentation $documentation, DeleteDocumentation $deleteDocumentation): RedirectResponse
    {
        Gate::authorize('delete', $documentation);

        $deleteDocumentation->handle($documentation);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.deleted'),
        ]);

        return to_route('documentation.manage.index');
    }

    /**
     * Swap a document with its neighbour.
     *
     * Reordering is a repeated, incremental action, so it stays silent: a toast
     * per click would drown the list the author is looking at.
     */
    public function move(Request $request, Documentation $documentation, string $direction, MoveDocumentation $moveDocumentation): RedirectResponse
    {
        Gate::authorize('update', $documentation);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $moveDocumentation->handle($documentation, $direction);

        return back();
    }
}
