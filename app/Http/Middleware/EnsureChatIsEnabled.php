<?php

namespace App\Http\Middleware;

use App\Settings\ChatSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes every user-facing chat surface while the global toggle is off.
 *
 * The Chat Settings screen and the Super Admin audit surface deliberately do
 * not carry this middleware: the first is what switches chat back on, and the
 * second reads stored history, which the toggle never removes.
 */
class EnsureChatIsEnabled
{
    public function __construct(private readonly ChatSettings $settings) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->chatEnabled, 403, __('chat.message.disabled'));

        return $next($request);
    }
}
