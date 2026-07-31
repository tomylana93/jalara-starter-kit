/**
 * One notification, in the single shape the server uses for both the persisted
 * history and the realtime broadcast. `url` is an optional in-app destination
 * and is always rendered as text, never as HTML.
 */
export type NotificationItem = {
    id: string;
    type: string;
    title: string;
    message: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

/**
 * The bell's initial state, shared on every authenticated response.
 */
export type NotificationBellState = {
    items: NotificationItem[];
    unreadCount: number;
};

export type NotificationFilter = 'all' | 'unread';

export type NotificationPageMeta = {
    page: number;
    perPage: number;
    total: number;
    lastPage: number;
    from: number | null;
    to: number | null;
};

export type NotificationPayload = {
    data: NotificationItem[];
    meta: NotificationPageMeta;
};
