<?php

namespace App\Http\Middleware;

use App\Actions\Chat\LoadSharedChatState;
use App\Actions\Notifications\LoadNotificationBell;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Presenters\BrandingPresenter;
use App\Http\Presenters\NotificationPresenter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
        'chat',
        'chat/*',
        'settings',
        'settings/*',
        'documentation',
        'documentation/*',
    ];

    public function __construct(
        private readonly LoadNotificationBell $loadNotificationBell,
        private readonly LoadSharedChatState $loadSharedChatState,
    ) {}

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
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'description' => config('app.description'),
            'locale' => app()->getLocale(),
            'fallbackLocale' => config('app.fallback_locale'),
            'branding' => BrandingPresenter::present(),
            'auth' => [
                'user' => $user,
            ],
            'can' => [
                'manageSettings' => $user?->can(Permission::ManageSettings->value) ?? false,
                'viewUsers' => $user?->can(Permission::ViewUsers->value) ?? false,
                /* Gates the Super Admin's read-only chat audit entry. */
                'auditChat' => $user?->hasRole(Role::SuperAdmin->value) ?? false,
                'manageDocumentation' => $user?->hasRole(Role::SuperAdmin->value) ?? false,
            ],
            /*
             * Deliberately not named "notifications": the notification page
             * sends its own paginated prop under that key, which would override
             * this shared one and leave the bell without its state.
             */
            'notificationBell' => NotificationPresenter::presentBell(
                $this->loadNotificationBell->handle($user instanceof User ? $user : null)
            ),
            /*
             * One server-owned source for the navigation entry, the bell, and
             * the desktop widget, so no surface has to guess whether chat is
             * available or how much is waiting.
             */
            'chat' => $this->loadSharedChatState->handle($user instanceof User ? $user : null),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
