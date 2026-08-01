<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\UpdateReaction;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\UpdateReactionRequest;
use App\Models\Chat\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReactionController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function update(
        UpdateReactionRequest $request,
        Message $message,
        UpdateReaction $updateReaction,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $message->load('conversation.participants.user');

        Gate::authorize('react', [$message->conversation, $message]);

        $reaction = $updateReaction->handle($message, $user, (string) $request->validated('emoji'));

        return response()->json(['reaction' => ChatPresenter::reaction($reaction)]);
    }

    public function destroy(Request $request, Message $message, UpdateReaction $updateReaction): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $message->load('conversation.participants.user');

        Gate::authorize('react', [$message->conversation, $message]);

        $updateReaction->handle($message, $user, null);

        return response()->json(['reaction' => null]);
    }
}
