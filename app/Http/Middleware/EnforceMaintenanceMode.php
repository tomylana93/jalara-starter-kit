<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use App\Settings\SecuritySettings;
use App\Settings\SettingsResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceMaintenanceMode
{
    /**
     * Handle an incoming request while administrative maintenance is enabled.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenanceEnabled() || $this->isAlwaysReachable($request)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User && $user->can(Permission::ManageSettings->value)) {
            return $next($request);
        }

        /*
         * Aborting rather than returning a response is what lets this render as
         * a full Inertia page: `AppServiceProvider::configureErrorPages()` turns
         * the 503 into the `Maintenance` component, while the JSON rule in
         * `bootstrap/app.php` keeps API clients on the same `message` body they
         * received before.
         */
        abort(503, __('maintenance.message'));
    }

    /**
     * Determine whether administrative maintenance is enabled.
     */
    private function maintenanceEnabled(): bool
    {
        return SettingsResolver::tryResolve(SecuritySettings::class)->maintenanceEnabled ?? false;
    }

    /**
     * Determine whether the route stays reachable during maintenance.
     *
     * Sign-in and password recovery are matched by pattern because Fortify names
     * a form and its submission separately. Allowing `login` but not
     * `login.store` renders the sign-in screen and then rejects it, and the
     * `manage settings` bypass cannot help because the request is still
     * unauthenticated at that point — which locks out every account, including
     * the one holding the switch that turns maintenance off.
     *
     * `home` is listed because Fortify sends a sign-out to `/`, and that route
     * only dispatches to the dashboard or the sign-in screen. Blocking it
     * answers a sign-out with the maintenance notice instead of the sign-in
     * screen; it exposes nothing, because the dashboard it may point to
     * enforces maintenance on its own.
     */
    private function isAlwaysReachable(Request $request): bool
    {
        if ($request->is('up')) {
            return true;
        }

        return $request->routeIs(
            'home',
            'login',
            'login.*',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'settings.*',
        );
    }
}
