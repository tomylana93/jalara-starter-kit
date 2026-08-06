<?php

namespace App\Actions\Documentation;

use App\Models\DocumentationCategory;

final class UpdateDocumentationCategory
{
    /**
     * Rename a category; its position is only ever changed by a move.
     *
     * @param  array{name: string}  $attributes
     */
    public function handle(DocumentationCategory $documentationCategory, array $attributes): DocumentationCategory
    {
        $documentationCategory->update($attributes);

        return $documentationCategory;
    }
}
