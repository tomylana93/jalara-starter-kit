<?php

namespace App\Models;

use Database\Factories\DocumentationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
