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

        $this->endSession($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return to_route('login')->withErrors(['email' => $message]);
    }

    /**
     * End the browser session, when this request carries one.
     *
     * `auth:sanctum` calls `Auth::shouldUse('sanctum')`, so the default guard on
     * an API request is the stateless `RequestGuard`, which has no `logout()`
     * and no session behind it. Nothing needs ending there: the token is
     * re-checked on every request, and the account status with it.
     */
    private function endSession(Request $request): void
    {
        if ($request->hasSession()) {
            Auth::guard((string) config('auth.defaults.guard'))->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        /*
         * Sanctum starts a session only for a request it recognizes as coming
         * from the frontend, so a token request has none of the above to undo -
         * but its stateless guard has already resolved this user and is now the
         * default. Forgetting every resolved guard is what makes the block hold
         * for the rest of the request, whichever one authenticated it.
         */
        Auth::forgetGuards();
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
