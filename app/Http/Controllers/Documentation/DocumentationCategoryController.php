<?php

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\CreateDocumentationCategory;
use App\Actions\Documentation\DeleteDocumentationCategory;
use App\Actions\Documentation\MoveDocumentationCategory;
use App\Actions\Documentation\UpdateDocumentationCategory;
use App\Enums\MoveDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\StoreDocumentationCategoryRequest;
use App\Http\Requests\Documentation\UpdateDocumentationCategoryRequest;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
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
    public function destroy(DocumentationCategory $documentationCategory, DeleteDocumentationCategory $deleteDocumentationCategory): RedirectResponse
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
    public function move(DocumentationCategory $documentationCategory, MoveDirection $direction, MoveDocumentationCategory $moveDocumentationCategory): RedirectResponse
    {
        Gate::authorize('update', $documentationCategory);

        $moveDocumentationCategory->handle($documentationCategory, $direction);

        return back();
    }
}
