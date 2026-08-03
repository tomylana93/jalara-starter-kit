<?php

namespace App\Http\Controllers\Chat;

use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * The inbox page name; kept distinct from the transcript's so the two
     * scroll containers never fight over a single `page` query parameter.
     */
    public const string CONVERSATIONS_PAGE = 'conversations';

    public const string MESSAGES_PAGE = 'messages';

    /**
     * Show the dedicated chat page.
     *
     * Both lists are Inertia scroll props: the server owns paging and Inertia
     * merges each page into what the client already holds, so scrolling up
     * through history prepends older messages instead of replacing the window
     * on screen.
     *
     * A `conversation` query parameter deep-links straight to one direct
     * message; the policy still decides whether any content is returned.
     */
    public function index(Request $request): Response
    {
        $user = $this->authenticatedUser($request);
        $active = $this->activeConversation($request);

        $conversations = Conversation::inboxFor($user)->paginate(
            perPage: Conversation::PER_PAGE,
            pageName: self::CONVERSATIONS_PAGE,
        );

        $unread = Conversation::unreadCountsFor($conversations->getCollection(), $user);

        /* Presented in place: the page metadata the scroll prop reads is unchanged. */
        $conversations->through(fn (Conversation $conversation): array => ChatPresenter::conversation(
            $conversation,
            $user,
            $unread[$conversation->id] ?? 0,
        ));

        $messages = $this->messageWindow($active)->paginate(
            perPage: Message::WINDOW,
            pageName: self::MESSAGES_PAGE,
        );

        $messages->through(
            fn (Message $message): array => ChatPresenter::message($message),
        );

        return Inertia::render('chat/Index', [
            'conversations' => Inertia::scroll($conversations),
            'messages' => Inertia::scroll($messages),
            'activeConversation' => $active instanceof Conversation
                ? ChatPresenter::conversation($active, $user)
                : null,
        ]);
    }

    /**
     * Resolve the conversation named by the query string, when the viewer may
     * see it.
     */
    private function activeConversation(Request $request): ?Conversation
    {
        $requested = $request->query('conversation');

        if (! is_string($requested) || ! Str::isUuid($requested)) {
            return null;
        }

        /* Only what the policy reads; a stranger never costs a presentation query. */
        $conversation = Conversation::query()
            ->with('participants.user')
            ->find($requested);

        if (! $conversation instanceof Conversation) {
            return null;
        }

        Gate::authorize('view', $conversation);

        return $conversation->load('participants.user.roles', 'latestMessage.reactions');
    }

    /**
     * The transcript query, newest first.
     *
     * The order is deliberately descending: page one is the live edge and each
     * further page walks back into history, which is what reverse infinite
     * scroll asks the server for.
     *
     * @return Builder<Message>
     */
    private function messageWindow(?Conversation $conversation): Builder
    {
        $query = $conversation instanceof Conversation
            ? $conversation->messages()->getQuery()
            /* No conversation open: the column is NOT NULL, so this window is empty. */
            : Message::query()->whereNull('conversation_id');

        return $query->with('reactions')->latest()->orderByDesc('id');
    }
}
