<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\StoreDocumentationCategoryRequest;
use App\Http\Requests\Documentation\UpdateDocumentationCategoryRequest;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DocumentationCategoryController extends Controller
{
    public function store(StoreDocumentationCategoryRequest $request): RedirectResponse
    {
        DocumentationCategory::query()->create([
            ...$request->validated(),
            'position' => DocumentationCategory::query()->max('position') + 1,
        ]);

        return back();
    }

    public function update(UpdateDocumentationCategoryRequest $request, DocumentationCategory $documentationCategory): RedirectResponse
    {
        $documentationCategory->update($request->validated());

        return back();
    }

    public function destroy(Request $request, DocumentationCategory $documentationCategory): RedirectResponse
    {
        Gate::authorize('delete', $documentationCategory);

        if ($documentationCategory->documentations()->exists()) {
            throw ValidationException::withMessages(['category' => __('documentation.validation.category_in_use')]);
        }

        $documentationCategory->delete();

        return back();
    }

    public function move(Request $request, DocumentationCategory $documentationCategory, string $direction): RedirectResponse
    {
        Gate::authorize('update', $documentationCategory);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($documentationCategory, $direction): void {
            $operator = $direction === 'up' ? '<' : '>';
            $order = $direction === 'up' ? 'desc' : 'asc';
            $adjacent = DocumentationCategory::query()->where('position', $operator, $documentationCategory->position)->orderBy('position', $order)->first();

            if ($adjacent !== null) {
                [$documentationCategory->position, $adjacent->position] = [$adjacent->position, $documentationCategory->position];
                $documentationCategory->save();
                $adjacent->save();
            }
        });

        return back();
    }
}
