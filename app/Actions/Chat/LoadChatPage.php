<?php

namespace App\Actions\Chat;

use App\Data\Chat\LoadChatPageResult;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class LoadChatPage
{
    public const string CONVERSATIONS_PAGE = 'conversations';

    public const string MESSAGES_PAGE = 'messages';

    /**
     * Orchestrate the chat page loading, including inbox, unread counts,
     * authorization of the active conversation, and transcript messages.
     */
    public function handle(User $user, ?string $requestedConversationId = null): LoadChatPageResult
    {
        $active = $this->activeConversation($requestedConversationId);

        $conversations = Conversation::inboxFor($user)->paginate(
            perPage: Conversation::PER_PAGE,
            pageName: self::CONVERSATIONS_PAGE,
        );

        $unread = Conversation::unreadCountsFor($conversations->getCollection(), $user);

        $messages = $this->messageWindow($active)->paginate(
            perPage: Message::WINDOW,
            pageName: self::MESSAGES_PAGE,
        );

        return new LoadChatPageResult(
            conversations: $conversations,
            unread: $unread,
            messages: $messages,
            activeConversation: $active,
        );
    }

    /**
     * Resolve the conversation named by the query string, when the viewer may
     * see it.
     */
    private function activeConversation(?string $requested): ?Conversation
    {
        if ($requested === null || $requested === '' || ! Str::isUuid($requested)) {
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
