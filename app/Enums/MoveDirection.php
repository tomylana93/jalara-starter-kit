<?php

namespace App\Enums;

use SortDirection;

/**
 * Which neighbour a manually ordered record swaps with.
 *
 * Bound implicitly from the route parameter, so an unknown direction is a 404
 * from the router and never reaches a controller or an action. Before this
 * existed the value arrived as a string, and anything that was not `up` read
 * as `down` — the failure this type removes rather than guards against.
 */
enum MoveDirection: string
{
    case Up = 'up';
    case Down = 'down';

    /**
     * The comparison that finds the neighbour on this side of the record.
     */
    public function comparison(): string
    {
        return match ($this) {
            self::Up => '<',
            self::Down => '>',
        };
    }

    /**
     * The ordering that brings the nearest neighbour first.
     */
    public function ordering(): SortDirection
    {
        return match ($this) {
            self::Up => SortDirection::Descending,
            self::Down => SortDirection::Ascending,
        };
    }
}
