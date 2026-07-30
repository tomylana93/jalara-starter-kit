<?php

namespace App\Http\Middleware;

use App\Settings\AuthenticationSettings;
use App\Settings\SettingsResolver;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedWhenRequired
{
    public function __construct(private readonly EnsureEmailIsVerified $ensureEmailIsVerified) {}

    /**
     * Enforce email verification only while the setting requires it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! $this->verificationRequired()) {
            return $next($request);
        }

        return $this->ensureEmailIsVerified->handle($request, $next, $redirectToRoute);
    }

    /**
     * Determine whether email verification is currently required.
     */
    private function verificationRequired(): bool
    {
        return SettingsResolver::tryResolve(AuthenticationSettings::class)->requireEmailVerification ?? true;
    }
}
