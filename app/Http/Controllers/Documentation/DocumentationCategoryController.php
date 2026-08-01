<?php

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\CreateDocumentationCategory;
use App\Actions\Documentation\DeleteDocumentationCategory;
use App\Actions\Documentation\MoveDocumentationCategory;
use App\Actions\Documentation\UpdateDocumentationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\StoreDocumentationCategoryRequest;
use App\Http\Requests\Documentation\UpdateDocumentationCategoryRequest;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DocumentationCategoryController extends Controller
{
    /**
     * Add a category to the documentation hierarchy.
     */
    public function store(StoreDocumentationCategoryRequest $request, CreateDocumentationCategory $createDocumentationCategory): RedirectResponse
    {
        $createDocumentationCategory->handle(['name' => (string) $request->validated('name')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.category_created'),
        ]);

        return back();
    }

    /**
     * Rename a category.
     */
    public function update(UpdateDocumentationCategoryRequest $request, DocumentationCategory $documentationCategory, UpdateDocumentationCategory $updateDocumentationCategory): RedirectResponse
    {
        $updateDocumentationCategory->handle($documentationCategory, ['name' => (string) $request->validated('name')]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.category_updated'),
        ]);

        return back();
    }

    /**
     * Delete a category that no longer holds any documentation.
     */
    public function destroy(Request $request, DocumentationCategory $documentationCategory, DeleteDocumentationCategory $deleteDocumentationCategory): RedirectResponse
    {
        Gate::authorize('delete', $documentationCategory);

        $deleteDocumentationCategory->handle($documentationCategory);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('documentation.message.category_deleted'),
        ]);

        return back();
    }

    /**
     * Swap a category with its neighbour.
     *
     * Reordering stays silent for the same reason document reordering does: it
     * is a repeated, immediately visible action.
     */
    public function move(Request $request, DocumentationCategory $documentationCategory, string $direction, MoveDocumentationCategory $moveDocumentationCategory): RedirectResponse
    {
        Gate::authorize('update', $documentationCategory);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $moveDocumentationCategory->handle($documentationCategory, $direction);

        return back();
    }
}
