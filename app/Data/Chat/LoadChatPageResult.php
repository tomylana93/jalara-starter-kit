<?php

namespace App\Data\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class LoadChatPageResult
{
    /**
     * @param  LengthAwarePaginator<int, Conversation>  $conversations
     * @param  array<string, int>  $unread
     * @param  LengthAwarePaginator<int, Message>  $messages
     */
    public function __construct(
        public LengthAwarePaginator $conversations,
        public array $unread,
        public LengthAwarePaginator $messages,
        public ?Conversation $activeConversation = null,
    ) {}
}
