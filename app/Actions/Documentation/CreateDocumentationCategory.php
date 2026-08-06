<?php

namespace App\Actions\Documentation;

use App\Data\Documentation\DocumentationCategoryData;
use App\Models\DocumentationCategory;

final class CreateDocumentationCategory
{
    /**
     * Append a category to the end of the manual ordering.
     */
    public function handle(DocumentationCategoryData $data): DocumentationCategory
    {
        return DocumentationCategory::query()->create([
            'name' => $data->name,
            'position' => DocumentationCategory::query()->max('position') + 1,
        ]);
    }
}
