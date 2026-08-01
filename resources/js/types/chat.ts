/**
 * A person as the chat surface sees them.
 *
 * The server sends nothing else about a user here: no email, no phone, no
 * storage path, and no exact account status. `available` is false once someone
 * can no longer receive messages; their history stays readable.
 */
export type ChatProfile = {
    /* UUIDv7, like every application model's primary key. */
    id: string;
    name: string;
    avatar: string | null;
    role: string | null;
    available: boolean;
};

/**
 * One stored message. Messages are immutable, so nothing edits these.
 */
export type ChatMessage = {
    id: string;
    conversation_id: string;
    sender_id: string;
    body: string | null;
    image: { url: string } | null;
    reactions: readonly ChatReaction[];
    created_at: string | null;
};

export type ChatReaction = {
    id: string;
    user_id: string;
    emoji: string;
};

export type ChatConversation = {
    id: string;
    participant: ChatProfile | null;
    last_message: ChatMessage | null;
    last_message_at: string | null;
    unread_count: number;
    /* The viewer's own read marker. */
    last_read_at: string | null;
    /* The other side's read marker, rendered as the read receipt. */
    peer_read_at: string | null;
};

export type ChatPageMeta = {
    page: number;
    perPage: number;
    total: number;
    lastPage: number;
};

export type ChatConversationPayload = {
    data: ChatConversation[];
    meta: ChatPageMeta;
};

export type ChatConversationWindow = {
    conversation: ChatConversation;
    messages: ChatMessage[];
    hasMore: boolean;
};

/**
 * Shared on every authenticated response, so the navigation entry, the badge,
 * and the desktop widget all read the same server-owned state.
 */
export type ChatSharedState = {
    enabled: boolean;
    imageUploadsEnabled: boolean;
    unreadCount: number;
};

export type ChatAuditConversation = {
    id: string;
    participants: ChatProfile[];
    last_message_at: string | null;
    message_count: number;
};

export type ChatAuditLog = {
    id: string;
    viewer: { id: string; name: string };
    viewed_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
};
