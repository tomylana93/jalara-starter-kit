<?php

namespace App\Http\Controllers\Documentation;

use App\Actions\Documentation\SaveDocumentation;
use App\Enums\DocumentationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\StoreDocumentationRequest;
use App\Http\Requests\Documentation\UpdateDocumentationRequest;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationWriteController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('documentation/manage/Edit', $this->editorProps());
    }

    public function store(StoreDocumentationRequest $request, SaveDocumentation $save): RedirectResponse
    {
        $documentation = $save->handle($request->documentationAttributes());

        return to_route('documentation.manage.documents.edit', $documentation);
    }

    public function edit(Documentation $documentation): Response
    {
        return Inertia::render('documentation/manage/Edit', $this->editorProps($documentation));
    }

    public function update(UpdateDocumentationRequest $request, Documentation $documentation, SaveDocumentation $save): RedirectResponse
    {
        $save->handle($request->documentationAttributes(), $documentation);

        return to_route('documentation.manage.documents.edit', $documentation);
    }

    public function destroy(Request $request, Documentation $documentation): RedirectResponse
    {
        Gate::authorize('delete', $documentation);
        $documentation->delete();

        return to_route('documentation.manage.index');
    }

    public function move(Request $request, Documentation $documentation, string $direction): RedirectResponse
    {
        Gate::authorize('update', $documentation);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($documentation, $direction): void {
            $operator = $direction === 'up' ? '<' : '>';
            $order = $direction === 'up' ? 'desc' : 'asc';
            $adjacent = Documentation::query()
                ->where('documentation_category_id', $documentation->documentation_category_id)
                ->where('position', $operator, $documentation->position)
                ->orderBy('position', $order)
                ->first();

            if ($adjacent !== null) {
                [$documentation->position, $adjacent->position] = [$adjacent->position, $documentation->position];
                $documentation->save();
                $adjacent->save();
            }
        });

        return back();
    }

    /** @return array<string, mixed> */
    private function editorProps(?Documentation $documentation = null): array
    {
        return [
            'documentation' => $documentation,
            'categories' => DocumentationCategory::query()->orderBy('position')->get(['id', 'name']),
            'statuses' => collect(DocumentationStatus::cases())->map(fn (DocumentationStatus $status): array => ['value' => $status->value, 'label' => __('documentation.status.'.$status->value)]),
        ];
    }
}
