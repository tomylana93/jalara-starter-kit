<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Http\Presenters\BrandingPresenter;
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
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
