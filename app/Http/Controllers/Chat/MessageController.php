<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Send one message, opening the conversation when it is the pair's first.
     *
     * Nothing is stored until the body validates, so browsing the recipient
     * directory never leaves an empty conversation behind.
     */
    public function store(
        StoreMessageRequest $request,
        StartConversation $startConversation,
        SendMessage $sendMessage,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);

        $conversation = $this->resolveConversation($request, $user, $startConversation);

        Gate::authorize('send', $conversation);

        $message = $sendMessage->handle($conversation, $user, (string) $request->validated('body'));

        $conversation->load('participants.user')->setRelation('latestMessage', $message);

        return response()->json([
            'conversation' => ChatPresenter::conversation($conversation, $user),
            'message' => ChatPresenter::message($message),
        ], 201);
    }

    /**
     * Resolve the conversation the message belongs to.
     */
    private function resolveConversation(
        StoreMessageRequest $request,
        User $user,
        StartConversation $startConversation,
    ): Conversation {
        $conversationId = $request->validated('conversation_id');

        if (is_string($conversationId)) {
            $conversation = Conversation::query()->with('participants.user')->findOrFail($conversationId);

            /*
             * Resolved before the send policy so a stranger's identifier answers
             * 403 rather than revealing anything about the conversation.
             */
            Gate::authorize('view', $conversation);

            return $conversation;
        }

        $recipient = User::query()->with('roles')->findOrFail((string) $request->validated('recipient_id'));

        if ($recipient->is($user) || $recipient->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'recipient_id' => __('chat.message.recipient_unavailable'),
            ]);
        }

        return $startConversation->handle($user, $recipient);
    }
}
