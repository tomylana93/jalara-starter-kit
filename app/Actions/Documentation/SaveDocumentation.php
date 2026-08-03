<?php

namespace App\Actions\Documentation;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Support\DocumentationContent;

final readonly class SaveDocumentation
{
    public function __construct(
        private ResolveUniqueDocumentationSlug $resolveSlug,
    ) {}

    /**
     * @param  array{documentation_category_id: string, title: string, slug?: string|null, status: string, content: array<string, mixed>}  $attributes
     */
    public function handle(array $attributes, ?Documentation $documentation = null): Documentation
    {
        $documentation ??= new Documentation;
        $slug = $documentation->published_at === null
            ? $this->resolveSlug->handle(($attributes['slug'] ?? null) ?: $attributes['title'], $documentation)
            : $documentation->slug;
        $status = DocumentationStatus::from($attributes['status']);

        $documentation->fill([
            ...$attributes,
            'slug' => $slug,
            'searchable_text' => DocumentationContent::text($attributes['content']),
            'published_at' => $status === DocumentationStatus::Published
                ? ($documentation->published_at ?? now())
                : $documentation->published_at,
            'position' => $documentation->exists
                ? $documentation->position
                : Documentation::query()->where('documentation_category_id', $attributes['documentation_category_id'])->max('position') + 1,
        ])->save();

        return $documentation;
    }
}
