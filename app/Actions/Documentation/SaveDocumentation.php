<?php

namespace App\Actions\Documentation;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Support\DocumentationContent;
use Illuminate\Support\Str;

final class SaveDocumentation
{
    /**
     * @param  array{documentation_category_id: string, title: string, slug?: string|null, status: string, content: array<string, mixed>}  $attributes
     */
    public function handle(array $attributes, ?Documentation $documentation = null): Documentation
    {
        $documentation ??= new Documentation;
        $slug = $documentation->published_at === null
            ? $this->uniqueSlug(($attributes['slug'] ?? null) ?: $attributes['title'], $documentation)
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

    private function uniqueSlug(string $value, Documentation $documentation): string
    {
        $base = Str::slug($value) ?: 'documentation';
        $slug = $base;
        $suffix = 2;

        while (Documentation::query()->where('slug', $slug)->when($documentation->exists, fn ($query) => $query->whereKeyNot($documentation->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
