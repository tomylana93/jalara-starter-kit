<?php

namespace App\Actions\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final readonly class LoadNotificationBellResult
{
    /**
     * @param  Collection<int, DatabaseNotification>  $items
     */
    public function __construct(
        public Collection $items,
        public int $unreadCount,
    ) {}
}
