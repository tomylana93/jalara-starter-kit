<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\LoadChatPage;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * The inbox page name; kept distinct from the transcript's so the two
     * scroll containers never fight over a single `page` query parameter.
     */
    public const string CONVERSATIONS_PAGE = LoadChatPage::CONVERSATIONS_PAGE;

    public const string MESSAGES_PAGE = LoadChatPage::MESSAGES_PAGE;

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
    public function index(Request $request, LoadChatPage $loadChatPage): Response
    {
        $user = $this->authenticatedUser($request);
        $conversationId = $request->query('conversation');

        $result = $loadChatPage->handle(
            $user,
            is_string($conversationId) ? $conversationId : null
        );

        $conversations = $result->conversations;
        $unread = $result->unread;
        $messages = $result->messages;
        $active = $result->activeConversation;

        /* Presented in place: the page metadata the scroll prop reads is unchanged. */
        $conversations->through(fn (Conversation $conversation): array => ChatPresenter::conversation(
            $conversation,
            $user,
            $unread[$conversation->id] ?? 0,
        ));

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
}
