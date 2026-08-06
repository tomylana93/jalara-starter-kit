<?php

namespace App\Http\Presenters;

use App\Data\Notifications\LoadNotificationBellResult;
use App\Notifications\RealtimeTestNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds the notification payload shared with the client.
 *
 * The Eloquent record is never shared: only this explicit array of scalars
 * crosses the boundary, and it mirrors the broadcast payload produced by
 * {@see RealtimeTestNotification} so one client-side type
 * renders both the persisted history and realtime arrivals.
 */
final class NotificationPresenter
{
    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     title: string,
     *     message: string,
     *     url: string|null,
     *     read_at: string|null,
     *     created_at: string|null,
     * }
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => (string) $notification->id,
            'type' => self::string($data, 'type'),
            'title' => self::string($data, 'title'),
            'message' => self::string($data, 'message'),
            'url' => self::nullableString($data, 'url'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    /**
     * Format a notification bell result into the payload expected by the client.
     *
     * @return array{
     *     items: list<array{
     *         id: string,
     *         type: string,
     *         title: string,
     *         message: string,
     *         url: string|null,
     *         read_at: string|null,
     *         created_at: string|null,
     *     }>,
     *     unreadCount: int,
     * }
     */
    public static function presentBell(LoadNotificationBellResult $result): array
    {
        return [
            'items' => self::presentMany($result->items),
            'unreadCount' => $result->unreadCount,
        ];
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return list<array{
     *     id: string,
     *     type: string,
     *     title: string,
     *     message: string,
     *     url: string|null,
     *     read_at: string|null,
     *     created_at: string|null,
     * }>
     */
    public static function presentMany(Collection $notifications): array
    {
        return array_values($notifications->map(self::present(...))->all());
    }

    /**
     * Format a notification paginator into the payload expected by the client.
     *
     * @param  LengthAwarePaginator<int, DatabaseNotification>  $paginator
     * @return array{
     *     data: list<array{
     *         id: string,
     *         type: string,
     *         title: string,
     *         message: string,
     *         url: string|null,
     *         read_at: string|null,
     *         created_at: string|null,
     *     }>,
     *     meta: array{
     *         page: int,
     *         perPage: int,
     *         total: int,
     *         lastPage: int,
     *         from: int|null,
     *         to: int|null,
     *     }
     * }
     */
    public static function presentPage(LengthAwarePaginator $paginator): array
    {
        $page = $paginator->currentPage();
        $count = count($paginator->items());
        $perPage = $paginator->perPage();
        $from = $count === 0 ? null : (($page - 1) * $perPage) + 1;

        return [
            'data' => self::presentMany($paginator->getCollection()),
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
                'from' => $from,
                'to' => $from === null ? null : ($from + $count) - 1,
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
