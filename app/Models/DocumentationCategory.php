<?php

namespace App\Models;

use App\Enums\DocumentationStatus;
use Database\Factories\DocumentationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @property string $id
 * @property string $name
 * @property int $position
 * @property-read Collection<int, Documentation> $documentations
 */
#[Fillable(['name', 'position'])]
class DocumentationCategory extends Model
{
    /** @use HasFactory<DocumentationCategoryFactory> */
    use HasFactory, HasUuids;

    /** @return HasMany<Documentation, $this> */
    public function documentations(): HasMany
    {
        return $this->hasMany(Documentation::class);
    }

    /**
     * Categories carrying published documents, ordered and loaded exactly as
     * the reader navigation renders them.
     *
     * The navigation shows a title and links by slug, so the document body and
     * its search index stay out of the payload; the reader page loads the one
     * document it displays separately.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function forReaderNavigation(Builder $query): void
    {
        $query
            ->whereHas('documentations', fn ($documentations) => $documentations->where('status', DocumentationStatus::Published))
            ->orderBy('position')
            ->with('documentations', fn (Relation $documentations) => $documentations
                ->getQuery()
                ->where('status', DocumentationStatus::Published)
                ->select(['id', 'documentation_category_id', 'title', 'slug'])
                ->orderBy('position'));
    }
}
