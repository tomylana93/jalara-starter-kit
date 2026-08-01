<?php

namespace App\Tables;

use App\Http\Presenters\DocumentationPresenter;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The management listing of documents.
 *
 * The hierarchy is the ordering: documents follow their category's position and
 * then their own manual position, so the table exposes no sort, search, or page
 * size control. Only the page is negotiable.
 *
 * @extends AbstractTable<Documentation>
 */
final class DocumentationTable extends AbstractTable
{
    /**
     * The single ordering the table offers, kept whitelisted like any other.
     */
    public const array SORTABLE = ['position' => 'position'];

    /**
     * @return Builder<Documentation>
     */
    protected function query(): Builder
    {
        return Documentation::query()
            ->select(['id', 'documentation_category_id', 'title', 'slug', 'status', 'position'])
            ->with('category:id,name')
            /*
             * The category's position is the outer ordering, applied here so the
             * inherited document position and identifier append after it.
             */
            ->orderBy(DocumentationCategory::query()
                ->select('position')
                ->whereColumn('documentation_categories.id', 'documentations.documentation_category_id'));
    }

    /**
     * The management list has no search box.
     *
     * @return list<string>
     */
    protected function searchable(): array
    {
        return [];
    }

    /**
     * @return non-empty-array<string, string>
     */
    protected function sortable(): array
    {
        return self::SORTABLE;
    }

    protected function defaultSort(): string
    {
        return 'position';
    }

    /**
     * @param  Documentation  $model
     * @return array<string, mixed>
     */
    protected function transform(Model $model): array
    {
        return DocumentationPresenter::managementRow($model);
    }
}
