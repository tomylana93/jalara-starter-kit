# Chat Frontend

## Ownership split

- Inertia owns *paging*: `<InfiniteScroll>` requests and merges pages of the
  `conversations` and `messages` scroll props.
- The shadcn-vue `message-scroller` registry primitive owns the *viewport*:
  autoscroll, live edge, scroll-to-end button. It is not a pagination layer.
- `useChat` (module-level singleton) owns *realtime and the widget*: socket
  subscriptions, connection state, live arrivals, and the JSON endpoints the
  widget uses because it has no page props of its own.
- The chat page mirrors the Inertia-merged inbox into the store
  (`seedConversations` on prop change) and renders from the store, so a socket
  arrival can reorder the list and raise unread without a round trip. Live
  messages are merged after the reversed transcript and deduplicated by id.

## Registry primitives

- Message rows use `Message`/`MessageContent` + `Bubble`/`BubbleContent`; the
  transcript uses `MessageScroller*`; the composer uses `InputGroup`; date
  separators use `Marker`; reactions use `Popover`; image previews use
  `Attachment`. Long scroll containers use `scroll-fade-y`, and upload state
  uses the Attachment shimmer. Never hand-roll these, and never hand-edit
  `resources/js/components/ui/**` (see `mem:frontend/core`).
- Vitest stubs them in `resources/js/test/setup.ts`: the real scroller measures
  layout through ResizeObserver/MutationObserver, which jsdom lacks.
  `InfiniteScroll` is stubbed for the same reason.

## Desktop widget

- `ChatWidget` mounts in `AppShell` (both layout variants) and renders only at
  `lg` and above via `useMediaQuery`, never merely hidden with a class. It also
  hides itself on `/chat`, where the page owns the conversation.
- Open/minimized/conversation state and text drafts persist in user-scoped
  `sessionStorage` so they survive Inertia navigation without crossing account
  boundaries. `File` objects never persist; preview object URLs must be revoked.
- A minimized widget does not mark messages read; that is what keeps its
  notification alive. The widget never reports a page context - only the Chat
  page does, and that silences every DM (see `mem:backend/chat`).

## Chat page context

- `pages/chat/Index.vue` mints one opaque id per page instance
  (`crypto.randomUUID`, with a random-token fallback for non-secure contexts),
  POSTs `chat.context.store?context=<id>` on mount, refreshes it on a 60s
  interval, and DELETEs the same id on unmount. The id must stay fixed for the
  instance's lifetime, or a second tab would clear the first tab's suppression.
  Failures are swallowed: a missed report only means a notification arrives, and
  the server record expires anyway.

## Shared props

- `chat` (`{enabled, imageUploadsEnabled, unreadCount}`) and `can.auditChat`
  come from `HandleInertiaRequests`; navigation, upload controls, badges, and
  the widget read that server-owned state rather than deriving availability
  locally.
