<?php

namespace App\Actions\Notifications;

use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Settings\ChatSettings;
use App\Settings\SettingsResolver;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class PaginateNotifications
{
    private const int PER_PAGE = 10;

    /**
     * Paginate the user's notifications.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function handle(User $user, bool $unreadOnly, int $page = 1): LengthAwarePaginator
    {
        $relation = $unreadOnly
            ? $user->unreadNotifications()
            : $user->notifications();

        $query = $relation->getQuery()->orderBy('id', 'desc');

        if (SettingsResolver::tryResolve(ChatSettings::class)?->chatEnabled !== true) {
            ChatMessageNotification::excludeFrom($query);
        }

        $total = $query->toBase()->getCountForPagination();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $clampedPage = min(max($page, 1), $lastPage);

        return $query->paginate(
            perPage: self::PER_PAGE,
            page: $clampedPage,
            total: $total,
        );
    }
}
