<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $user->reactivateExpiredSuspension();

        if ($user->status !== UserStatus::Active) {
            return $this->blockedResponse($request, $user);
        }

        if ($user->must_change_password && ! $this->isPasswordChangeRoute($request)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.login.message.must_change_password')], 409)
                : to_route('account.security.edit');
        }

        return $next($request);
    }

    private function blockedResponse(Request $request, User $user): Response
    {
        $message = $user->status->message();

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return to_route('login')->withErrors(['email' => $message]);
    }

    private function isPasswordChangeRoute(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'password.confirm',
            'password.confirm.store',
            'account.security.edit',
            'account.password.update',
        );
    }
}
