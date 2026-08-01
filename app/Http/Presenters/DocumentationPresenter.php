<?php

namespace App\Http\Presenters;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Support\Collection;

/**
 * Builds every documentation payload shared with the client.
 *
 * No Eloquent record crosses the boundary: each page receives the explicit set
 * of fields it renders, so an added column never leaks into a response and the
 * TypeScript contracts describe exactly what arrives.
 */
final class DocumentationPresenter
{
    /**
     * A category as an option in the editor's category select.
     *
     * @return array{id: string, name: string}
     */
    public static function categoryOption(DocumentationCategory $documentationCategory): array
    {
        return [
            'id' => $documentationCategory->id,
            'name' => $documentationCategory->name,
        ];
    }

    /**
     * @param  Collection<int, DocumentationCategory>  $categories
     * @return list<array{id: string, name: string}>
     */
    public static function categoryOptions(Collection $categories): array
    {
        return array_values($categories->map(self::categoryOption(...))->all());
    }

    /**
     * A category in the management sidebar, with the size of its contents.
     *
     * @return array{id: string, name: string, position: int, documentations_count: int}
     */
    public static function managementCategory(DocumentationCategory $documentationCategory): array
    {
        return [
            'id' => $documentationCategory->id,
            'name' => $documentationCategory->name,
            'position' => $documentationCategory->position,
            'documentations_count' => (int) ($documentationCategory->documentations_count ?? 0),
        ];
    }

    /**
     * @param  Collection<int, DocumentationCategory>  $categories
     * @return list<array{id: string, name: string, position: int, documentations_count: int}>
     */
    public static function managementCategories(Collection $categories): array
    {
        return array_values($categories->map(self::managementCategory(...))->all());
    }

    /**
     * One row of the paginated management table.
     *
     * @return array{id: string, title: string, slug: string, status: string, category: array{id: string, name: string}}
     */
    public static function managementRow(Documentation $documentation): array
    {
        return [
            'id' => $documentation->id,
            'title' => $documentation->title,
            'slug' => $documentation->slug,
            'status' => $documentation->status->value,
            'category' => self::categoryOption($documentation->category),
        ];
    }

    /**
     * The props the hybrid create/edit editor page renders from.
     *
     * A create request has no document yet, which is what tells the page to
     * submit to the store route and to leave the slug editable.
     *
     * @param  Collection<int, DocumentationCategory>  $categories
     * @return array{
     *     documentation: array{id: string, documentation_category_id: string, title: string, slug: string, status: string, published_at: string|null, content: array<string, mixed>}|null,
     *     categories: list<array{id: string, name: string}>,
     *     statuses: list<array<string, mixed>>,
     * }
     */
    public static function editorProps(Collection $categories, ?Documentation $documentation = null): array
    {
        return [
            'documentation' => $documentation instanceof Documentation ? self::editorValue($documentation) : null,
            'categories' => self::categoryOptions($categories),
            'statuses' => DocumentationStatus::options(),
        ];
    }

    /**
     * The document being edited, in the shape the editor form binds to.
     *
     * @return array{id: string, documentation_category_id: string, title: string, slug: string, status: string, published_at: string|null, content: array<string, mixed>}
     */
    public static function editorValue(Documentation $documentation): array
    {
        return [
            'id' => $documentation->id,
            'documentation_category_id' => $documentation->documentation_category_id,
            'title' => $documentation->title,
            'slug' => $documentation->slug,
            'status' => $documentation->status->value,
            'published_at' => $documentation->published_at?->toISOString(),
            'content' => $documentation->content,
        ];
    }

    /**
     * A category and its documents as rendered by the reader navigation.
     *
     * @return array{id: string, name: string, documentations: list<array{id: string, title: string, slug: string}>}
     */
    public static function readerCategory(DocumentationCategory $documentationCategory): array
    {
        return [
            'id' => $documentationCategory->id,
            'name' => $documentationCategory->name,
            'documentations' => array_values($documentationCategory->documentations
                ->map(self::readerSummary(...))
                ->all()),
        ];
    }

    /**
     * @param  Collection<int, DocumentationCategory>  $categories
     * @return list<array{id: string, name: string, documentations: list<array{id: string, title: string, slug: string}>}>
     */
    public static function readerCategories(Collection $categories): array
    {
        return array_values($categories->map(self::readerCategory(...))->all());
    }

    /**
     * A document as a link in the reader index and sidebar.
     *
     * @return array{id: string, title: string, slug: string}
     */
    public static function readerSummary(Documentation $documentation): array
    {
        return [
            'id' => $documentation->id,
            'title' => $documentation->title,
            'slug' => $documentation->slug,
        ];
    }

    /**
     * The document the reader is currently displaying.
     *
     * @return array{id: string, title: string, slug: string, content: array<string, mixed>, category: array{id: string, name: string}}
     */
    public static function readerDetail(Documentation $documentation): array
    {
        return [
            ...self::readerSummary($documentation),
            'content' => $documentation->content,
            'category' => self::categoryOption($documentation->category),
        ];
    }
}
