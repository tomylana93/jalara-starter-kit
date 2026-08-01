<?php

namespace App\Actions\Documentation;

use App\Models\DocumentationCategory;
use Illuminate\Validation\ValidationException;

class DeleteDocumentationCategory
{
    /**
     * Remove a category once nothing is filed under it.
     *
     * Documents are never cascaded away with their category: the author has to
     * move or delete them first, so a rename-by-delete can not silently destroy
     * published documentation.
     *
     * @throws ValidationException
     */
    public function handle(DocumentationCategory $documentationCategory): void
    {
        if ($documentationCategory->documentations()->exists()) {
            throw ValidationException::withMessages(['category' => __('documentation.validation.category_in_use')]);
        }

        $documentationCategory->delete();
    }
}
