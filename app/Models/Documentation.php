<?php

namespace App\Models;

use App\Enums\DocumentationStatus;
use Carbon\CarbonInterface;
use Database\Factories\DocumentationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $documentation_category_id
 * @property string $title
 * @property string $slug
 * @property DocumentationStatus $status
 * @property int $position
 * @property array<string, mixed> $content
 * @property string $searchable_text
 * @property CarbonInterface|null $published_at
 * @property-read DocumentationCategory $category
 */
#[Fillable(['documentation_category_id', 'title', 'slug', 'status', 'position', 'content', 'searchable_text', 'published_at'])]
class Documentation extends Model
{
    /** @use HasFactory<DocumentationFactory> */
    use HasFactory, HasUuids;

    protected $attributes = [
        'status' => DocumentationStatus::Draft->value,
        'position' => 0,
    ];

    /** @return BelongsTo<DocumentationCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'documentation_category_id');
    }

    /**
     * @param  Builder<Documentation>  $query
     * @return Builder<Documentation>
     */
    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('status', DocumentationStatus::Published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'status' => DocumentationStatus::class,
            'content' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
