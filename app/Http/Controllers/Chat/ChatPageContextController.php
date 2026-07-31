<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\TrackChatPageContext;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatPageContextRequest;
use Illuminate\Http\JsonResponse;

/**
 * Reports whether the authenticated user currently has the Chat page open.
 *
 * The identifier names one open page instance, so several tabs can report
 * independently and closing one never silences or unsilences another. Every
 * context is stored under the caller's own user, so nobody can touch another
 * user's state, and none of it is shared with anyone else: this is not
 * presence. It only suppresses that user's own chat notifications, and it
 * expires by itself.
 */
class ChatPageContextController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Mark one Chat page instance as open, or refresh how long that stays true.
     */
    public function store(ChatPageContextRequest $request, TrackChatPageContext $context): JsonResponse
    {
        $context->open($this->authenticatedUser($request), (string) $request->validated('context'));

        return response()->json(['open' => true]);
    }

    /**
     * Mark one Chat page instance as closed.
     *
     * Any other tab of the same user keeps its own context, so notifications
     * only return once the last one is gone.
     */
    public function destroy(ChatPageContextRequest $request, TrackChatPageContext $context): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $context->close($user, (string) $request->validated('context'));

        return response()->json(['open' => $context->isOpen($user)]);
    }
}
