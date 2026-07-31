<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Http\Presenters\BrandingPresenter;
use App\Http\Presenters\NotificationPresenter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * How many of the newest notifications the bell dropdown receives.
     */
    private const int BELL_LIMIT = 5;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * The routes that must not be server rendered.
     *
     * Pages behind authentication render the sidebar shell, whose registry
     * components branch on viewport and colour-scheme media queries. The
     * server cannot know either, so server rendering them always produces
     * hydration mismatches. Guest pages use the auth layout and stay on SSR.
     *
     * @var array<int, string>
     */
    protected $withoutSsr = [
        'dashboard',
        'account',
        'account/*',
        'master-data',
        'master-data/*',
        'notifications',
        'settings',
        'settings/*',
    ];

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'description' => config('app.description'),
            'locale' => app()->getLocale(),
            'fallbackLocale' => config('app.fallback_locale'),
            'branding' => BrandingPresenter::present(),
            'auth' => [
                'user' => $request->user(),
            ],
            'can' => [
                'manageSettings' => $request->user()?->can(Permission::ManageSettings->value) ?? false,
                'viewUsers' => $request->user()?->can(Permission::ViewUsers->value) ?? false,
            ],
            /*
             * Deliberately not named "notifications": the notification page
             * sends its own paginated prop under that key, which would override
             * this shared one and leave the bell without its state.
             */
            'notificationBell' => $this->notificationBell($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Build the bell's initial state.
     *
     * Guests never reach the notification relation, so no query runs for them,
     * and only the newest few rows cross the boundary rather than the history.
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
    private function notificationBell(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return ['items' => [], 'unreadCount' => 0];
        }

        return [
            'items' => NotificationPresenter::presentMany(
                /*
                 * The relation sorts by created_at, which ties when several
                 * notifications land in the same second; the id keeps the order
                 * deterministic so the bell and the page agree.
                 */
                $user->notifications()->orderBy('id', 'desc')->limit(self::BELL_LIMIT)->get(),
            ),
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }
}
