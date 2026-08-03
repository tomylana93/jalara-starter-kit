<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\PaginateNotifications;
use App\Http\Presenters\NotificationPresenter;
use App\Http\Requests\Notifications\IndexNotificationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Show the authenticated user's notification history.
     */
    public function index(IndexNotificationRequest $request, PaginateNotifications $paginateNotifications): Response
    {
        $user = $this->user($request);

        $filter = $request->validated('filter');
        $filter = is_string($filter) ? $filter : self::FILTER_ALL;

        $unreadOnly = $filter === self::FILTER_UNREAD;
        $requestedPage = (int) ($request->validated('page') ?? 1);

        $paginator = $paginateNotifications->handle($user, $unreadOnly, $requestedPage);

        return Inertia::render('notifications/Index', [
            'notifications' => NotificationPresenter::presentPage($paginator),
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

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
