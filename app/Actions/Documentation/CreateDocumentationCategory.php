<?php

namespace App\Actions\Documentation;

use App\Models\DocumentationCategory;

final class CreateDocumentationCategory
{
    /**
     * Append a category to the end of the manual ordering.
     *
     * @param  array{name: string}  $attributes
     */
    public function handle(array $attributes): DocumentationCategory
    {
        return DocumentationCategory::query()->create([
            ...$attributes,
            'position' => DocumentationCategory::query()->max('position') + 1,
        ]);
    }
}
