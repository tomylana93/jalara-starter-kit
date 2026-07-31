<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Presenters\BrandingPresenter;
use App\Http\Presenters\NotificationPresenter;
use App\Models\Chat\Conversation;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Settings\ChatSettings;
use App\Settings\SettingsResolver;
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
        'chat',
        'chat/*',
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
                /* Gates the Super Admin's read-only chat audit entry. */
                'auditChat' => $request->user()?->hasRole(Role::SuperAdmin->value) ?? false,
            ],
            /*
             * Deliberately not named "notifications": the notification page
             * sends its own paginated prop under that key, which would override
             * this shared one and leave the bell without its state.
             */
            'notificationBell' => $this->notificationBell($request),
            /*
             * One server-owned source for the navigation entry, the bell, and
             * the desktop widget, so no surface has to guess whether chat is
             * available or how much is waiting.
             */
            'chat' => $this->chatState($request),
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

        $chatEnabled = $this->chatEnabled();

        return [
            'items' => NotificationPresenter::presentMany(
                /*
                 * The relation sorts by created_at, which ties when several
                 * notifications land in the same second; the id keeps the order
                 * deterministic so the bell and the page agree.
                 */
                $user->notifications()
                    ->unless($chatEnabled, ChatMessageNotification::excludeFrom(...))
                    ->orderBy('id', 'desc')
                    ->limit(self::BELL_LIMIT)
                    ->get(),
            ),
            'unreadCount' => $user->unreadNotifications()
                ->unless($chatEnabled, ChatMessageNotification::excludeFrom(...))
                ->count(),
        ];
    }

    /**
     * Build the shared chat state.
     *
     * `unreadCount` aggregates across every conversation, which is what the
     * navigation badge renders. It is zero while chat is off, so no count
     * survives a switched-off surface.
     *
     * @return array{enabled: bool, unreadCount: int}
     */
    private function chatState(Request $request): array
    {
        $user = $request->user();
        $enabled = $this->chatEnabled();

        if (! $enabled || ! $user instanceof User) {
            return ['enabled' => false, 'unreadCount' => 0];
        }

        return [
            'enabled' => true,
            'unreadCount' => Conversation::unreadMessageCountFor($user),
        ];
    }

    /**
     * Whether the chat surface is switched on.
     *
     * Resolved through the settings resolver so a request served during the
     * deployment window, before the settings table exists, still renders.
     */
    private function chatEnabled(): bool
    {
        return SettingsResolver::tryResolve(ChatSettings::class)->chatEnabled ?? false;
    }
}
