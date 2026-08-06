<?php

namespace App\Support\Users;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The users a table selection names, restored to the order they were picked in.
 *
 * Both export formats read a selection, and both promise the rows come back in
 * the order the operator ticked them. Keeping that promise in one place means
 * the spreadsheet and the document can never disagree about it.
 */
final class SelectedUsers
{
    /**
     * @param  list<string>  $ids
     * @return Collection<int, User>
     */
    public static function inSelectionOrder(array $ids): Collection
    {
        $position = array_flip($ids);

        return User::query()
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->with('roles:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (User $user): int => $position[$user->id] ?? PHP_INT_MAX)
            ->values();
    }
}
