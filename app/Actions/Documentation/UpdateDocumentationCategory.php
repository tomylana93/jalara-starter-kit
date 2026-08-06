<?php

namespace App\Actions\Documentation;

use App\Data\Documentation\DocumentationCategoryData;
use App\Models\DocumentationCategory;

final class UpdateDocumentationCategory
{
    /**
     * Rename a category; its position is only ever changed by a move.
     */
    public function handle(DocumentationCategory $documentationCategory, DocumentationCategoryData $data): DocumentationCategory
    {
        $documentationCategory->update(['name' => $data->name]);

        return $documentationCategory;
    }
}
