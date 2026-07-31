<?php

namespace App\Http\Controllers;

use App\Http\Presenters\NotificationPresenter;
use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every query in this controller starts from the authenticated user's own
 * notification relation, so one user can never read or mutate another's.
 */
class NotificationController extends Controller
{
    public const FILTER_ALL = 'all';

    public const FILTER_UNREAD = 'unread';

    /**
     * @var list<string>
     */
    public const FILTERS = [self::FILTER_ALL, self::FILTER_UNREAD];

    private const int PER_PAGE = 10;

    /**
     * Show the authenticated user's notification history.
     */
    public function index(IndexNotificationRequest $request): Response
    {
        $user = $this->user($request);

        $filter = $request->validated('filter');
        $filter = is_string($filter) ? $filter : self::FILTER_ALL;

        $query = $this->scopedQuery($user, $filter);

        /*
         * Laravel accepts any positive page, so a page past the end would answer
         * with an empty window instead of rows. Counting first lets the request
         * settle on the last page that exists; the count is handed to the
         * paginator so normalizing costs no extra query.
         */
        $total = $query->toBase()->getCountForPagination();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $requestedPage = (int) ($request->validated('page') ?? 1);

        $paginator = $query->paginate(
            perPage: self::PER_PAGE,
            page: min(max($requestedPage, 1), $lastPage),
            total: $total,
        );

        $page = $paginator->currentPage();
        $count = count($paginator->items());
        $from = $count === 0 ? null : (($page - 1) * self::PER_PAGE) + 1;

        return Inertia::render('notifications/Index', [
            'notifications' => [
                'data' => NotificationPresenter::presentMany($paginator->getCollection()),
                'meta' => [
                    'page' => $page,
                    'perPage' => self::PER_PAGE,
                    'total' => $total,
                    'lastPage' => $lastPage,
                    'from' => $from,
                    'to' => $from === null ? null : ($from + $count) - 1,
                ],
            ],
            'filter' => $filter,
        ]);
    }

    /**
     * Mark one of the authenticated user's notifications as read.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        /*
         * Resolved through the relation rather than by route binding, so another
         * user's identifier answers 404 and never reveals that the record exists.
         */
        $record = $this->user($request)->notifications()->findOrFail($notification);

        $record->markAsRead();

        return back();
    }

    /**
     * Mark every unread notification of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Both relations already sort by created_at descending. The id is added as a
     * tie-breaker because several notifications can share a timestamp, and an
     * ambiguous order would let a row repeat or vanish between pages.
     *
     * @return Builder<DatabaseNotification>
     */
    private function scopedQuery(User $user, string $filter): Builder
    {
        $relation = $filter === self::FILTER_UNREAD
            ? $user->unreadNotifications()
            : $user->notifications();

        return $relation->getQuery()->orderBy('id', 'desc');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
