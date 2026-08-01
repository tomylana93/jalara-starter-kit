<?php

namespace App\Actions\Documentation;

use App\Models\DocumentationCategory;
use Illuminate\Support\Facades\DB;

class MoveDocumentationCategory
{
    /**
     * Swap a category with its neighbour in the manual ordering.
     *
     * The direction is `up` or `down`; anything else is rejected by the route
     * before it reaches here, and would otherwise read as `down`.
     */
    public function handle(DocumentationCategory $documentationCategory, string $direction): void
    {
        DB::transaction(function () use ($documentationCategory, $direction): void {
            $adjacent = DocumentationCategory::query()
                ->where('position', $direction === 'up' ? '<' : '>', $documentationCategory->position)
                ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
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
