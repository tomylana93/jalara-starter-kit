<?php

namespace App\Actions\Documentation;

use App\Data\Documentation\DocumentationData;
use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Support\DocumentationContent;

final readonly class SaveDocumentation
{
    public function __construct(
        private ResolveUniqueDocumentationSlug $resolveSlug,
    ) {}

    public function handle(DocumentationData $data, ?Documentation $documentation = null): Documentation
    {
        $documentation ??= new Documentation;
        $slug = $documentation->published_at === null
            ? $this->resolveSlug->handle($data->slug ?: $data->title, $documentation)
            : $documentation->slug;

        $documentation->fill([
            'documentation_category_id' => $data->documentationCategoryId,
            'title' => $data->title,
            'slug' => $slug,
            'status' => $data->status,
            'content' => $data->content,
            'searchable_text' => DocumentationContent::text($data->content),
            'published_at' => $data->status === DocumentationStatus::Published
                ? ($documentation->published_at ?? now())
                : $documentation->published_at,
            'position' => $documentation->exists
                ? $documentation->position
                : Documentation::query()->where('documentation_category_id', $data->documentationCategoryId)->max('position') + 1,
        ])->save();

        return $documentation;
    }
}
