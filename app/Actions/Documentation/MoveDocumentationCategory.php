<?php

namespace App\Actions\Documentation;

use App\Enums\MoveDirection;
use App\Models\DocumentationCategory;
use Illuminate\Support\Facades\DB;

final class MoveDocumentationCategory
{
    /**
     * Swap a category with its neighbour in the manual ordering.
     *
     * A category already at the edge has no neighbour and stays where it is.
     */
    public function handle(DocumentationCategory $documentationCategory, MoveDirection $direction): void
    {
        DB::transaction(function () use ($documentationCategory, $direction): void {
            $adjacent = DocumentationCategory::query()
                ->where('position', $direction->comparison(), $documentationCategory->position)
                ->orderBy('position', $direction->ordering())
                ->first();

            if ($adjacent === null) {
                return;
            }

            [$documentationCategory->position, $adjacent->position] = [$adjacent->position, $documentationCategory->position];
            $documentationCategory->save();
            $adjacent->save();
        });
    }
}
