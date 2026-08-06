<?php

namespace App\Actions\Documentation;

use App\Enums\MoveDirection;
use App\Models\Documentation;
use Illuminate\Support\Facades\DB;

final class MoveDocumentation
{
    /**
     * Swap a document with its neighbour inside the same category.
     *
     * Positions are manual integers, so reordering exchanges the two values
     * instead of renumbering the whole category. A document already at the edge
     * has no neighbour and simply stays where it is.
     */
    public function handle(Documentation $documentation, MoveDirection $direction): void
    {
        DB::transaction(function () use ($documentation, $direction): void {
            $adjacent = Documentation::query()
                ->where('documentation_category_id', $documentation->documentation_category_id)
                ->where('position', $direction->comparison(), $documentation->position)
                ->orderBy('position', $direction->ordering())
                ->first();

            if ($adjacent === null) {
                return;
            }

            [$documentation->position, $adjacent->position] = [$adjacent->position, $documentation->position];
            $documentation->save();
            $adjacent->save();
        });
    }
}
