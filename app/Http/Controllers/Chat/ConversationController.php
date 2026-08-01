<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\MarkConversationRead;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\IndexConversationRequest;
use App\Http\Requests\Chat\MarkConversationReadRequest;
use App\Http\Requests\Chat\ShowConversationRequest;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Standalone JSON endpoints backing the chat page and the desktop widget.
 *
 * Every action starts from the authenticated user and passes the conversation
 * through the policy, so a non-participant can neither list nor read a direct
 * message that is not theirs.
 */
class ConversationController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * List one page of the viewer's inbox, newest activity first.
     */
    public function index(IndexConversationRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $page = (int) ($request->validated('page') ?? 1);

        $conversations = Conversation::inboxFor($user)
            ->paginate(perPage: Conversation::PER_PAGE, page: max($page, 1));

        return response()->json([
            'data' => ChatPresenter::conversations(
                $conversations->getCollection(),
                $user,
                Conversation::unreadCountsFor($conversations->getCollection(), $user),
            ),
            'meta' => [
                'page' => $conversations->currentPage(),
                'perPage' => Conversation::PER_PAGE,
                'total' => $conversations->total(),
                'lastPage' => $conversations->lastPage(),
            ],
        ]);
    }

    /**
     * Read one window of a conversation, oldest first within the window.
     *
     * Without `before` the newest messages are returned; with it, the window
     * that precedes the message the client already holds, which is what the
     * upward infinite scroll asks for.
     */
    public function show(ShowConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $conversation->load('participants.user', 'latestMessage');

        Gate::authorize('view', $conversation);

        $query = $conversation->messages()->with('reactions')->latest()->orderByDesc('id');

        $before = $request->validated('before');

        if (is_string($before)) {
            $anchor = $conversation->messages()->whereKey($before)->first();

            if ($anchor instanceof Message) {
                $query->where(function ($scoped) use ($anchor): void {
                    $scoped->where('created_at', '<', $anchor->created_at)
                        ->orWhere(function ($tie) use ($anchor): void {
                            $tie->where('created_at', $anchor->created_at)->where('id', '<', $anchor->id);
                        });
                });
            }
        }

        /* One extra row answers "is there more history" without a count query. */
        $messages = $query->limit(Message::WINDOW + 1)->get();
        $hasMore = $messages->count() > Message::WINDOW;

        $window = $messages->take(Message::WINDOW)->reverse()->values();

        return response()->json([
            'conversation' => ChatPresenter::conversation(
                $conversation,
                $user,
                Conversation::unreadCountsFor(collect([$conversation]), $user)[$conversation->id] ?? 0,
            ),
            'messages' => ChatPresenter::messages($window),
            'hasMore' => $hasMore,
        ]);
    }

    /**
     * Move the viewer's read marker to a message they actually saw.
     */
    public function read(
        MarkConversationReadRequest $request,
        Conversation $conversation,
        MarkConversationRead $markConversationRead,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);

        $conversation->load('participants.user');

        Gate::authorize('view', $conversation);

        $message = $conversation->messages()->whereKey($request->validated('message_id'))->firstOrFail();

        $participant = $markConversationRead->handle($conversation, $user, $message->created_at ?? now());

        return response()->json([
            'conversation_id' => $conversation->id,
            'last_read_at' => $participant?->last_read_at?->toIso8601String(),
        ]);
    }
}
