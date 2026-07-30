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

        $message = __('setting.maintenance.message');

        return $request->expectsJson()
            ? response()->json(['message' => $message], 503)
            : response($message, 503);
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
     */
    private function isAlwaysReachable(Request $request): bool
    {
        if ($request->is('up')) {
            return true;
        }

        return $request->routeIs('login', 'logout', 'settings.*');
    }
}
