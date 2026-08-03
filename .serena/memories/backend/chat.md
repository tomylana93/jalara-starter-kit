# Chat (Direct Messages)

## Domain shape

- Tables: `chat_conversations` (canonical `participant_key` = both user UUIDs
  sorted and joined, unique - this index is what prevents a duplicate DM for a
  pair), `chat_participants` (per-side `last_read_at`), `chat_messages`
  (immutable text, one private image, or both), `chat_message_reactions`
  (one reaction per user/message), `chat_audit_logs` (access
  metadata only, permanent, never a copy of a body).
- A conversation is never created on its own: `SendMessage` is the only writer,
  so an abandoned recipient search leaves nothing behind.
- Participants read their own DMs; a Super Admin is *refused* the conversation
  channel and the participant routes, and reads only through the audited
  `chat.audit.*` surface.

## Notification context without presence

- `SendMessage` dispatches `DeliverChatMessageNotification` (queued), which is
  transport only: recipient selection, suppression, replacement, and sending
  live in `NotifyChatMessageRecipient` and stay callable without a worker.
  Two independent checks silence it, and neither is presence (nothing is
  broadcast, no other user can observe either):
  - `TrackChatPageContext` - a cache record the Chat *page* refreshes while it
    is open (TTL 90s per context, client refresh 60s, cleared on unmount). It
    suppresses *every* chat notification for that user, because the page shows
    every conversation. It expires on its own, so a tab that dies does not
    silence the user forever. Only `chat.context.store` /
    `chat.context.destroy` write it, and only for the caller.
    The record is a **set of independently expiring contexts keyed by page
    instance**, not one flag: a user may have the page open in several tabs, and
    a single flag let the first tab to close unsilence the rest. A tab may only
    remove its own id; `isOpen()` is true while any context survives. The
    identifier is opaque and carries no authority - it is always scoped to the
    authenticated user, so reusing someone else's id only touches your own
    record. The read-modify-write runs under `Cache::lock` when the store is a
    `LockProvider`, falling back to a plain write otherwise; a lost context is
    benign because the client's next refresh restores it.
  - The recipient's `last_read_at`, re-read *after* the queue delay: a client
    already looking at that DM has moved its marker past the message. This is
    what scopes the *widget* to the one conversation it shows; a minimized
    widget deliberately does not mark read, so its notification survives.
- Exactly one active notification per conversation: the job deletes the
  recipient's existing unread chat notification before sending the new one.
- `App\Actions\Chat\LoadUnreadConversationNotifications` is the single owner of
  selecting a user's unread `ChatMessageNotification` records for one
  conversation: constrain by notification `type` in SQL, then filter the exact
  `data.conversation_id` in PHP, because `notifications.data` is a text column
  with no JSON index. Its two callers keep deliberately different terminal
  semantics: `NotifyChatMessageRecipient` deletes the matches before sending the
  replacement (no read history is retained), while `MarkConversationRead` marks
  them read and preserves the rows.
- The chat toggle hides chat notifications from the bell and the notification
  page (`ChatMessageNotification::excludeFrom()`); rows are never deleted.

## Presentation graph

- Every response path that hands participant users to `ChatPresenter::profile`,
  `conversation`, or `participants` must eager-load `user.roles`: the role label
  goes through Spatie `getRoleNames()`, whose `loadMissing('roles')` is an N+1
  on any list. Policy-only resolution loads `participants.user` first and adds
  the presentation-only `roles` (and reactions) after authorization passes, so a
  stranger's identifier never costs a presentation query.

## Page loading & Querying

- `App\Actions\Chat\LoadChatPage` handles orchestrating the page load: active-conversation resolution, authorization-sensitive graph loading (roles and reactions loaded only after authorization), inbox pagination, bulk unread counts, and transcript pagination.
- It returns untransformed paginators within `LoadChatPageResult`, while `ChatController` keeps `through()` presentation mapping in local variables because mapped `LengthAwarePaginator` template types cannot safely cross a declared result boundary due to Larastan constraints.
- `App\Actions\Chat\LoadSharedChatState` handles resolving the shared chat layout state: safe chat-setting resolution and aggregate navigation unread calculation. Guests and chat-disabled requests return `enabled=false`, `imageUploadsEnabled=false`, and `unreadCount=0` without querying conversation messages.
- HTTP submission is one action: `SubmitChatMessage` owns target resolution,
  authorization, first-conversation compensation, and the image branch, and
  returns `SubmitChatMessageResult` (sent message vs accepted upload) so the
  controller keeps the status codes.

## Images and reactions

- A message requires text, one image, or both. Images are private `local`-disk
  files (PNG/JPEG/WebP, <= 2 MiB, <= 2048 px per dimension); only a participant
  endpoint or the separately audited Super Admin endpoint may serve them, with
  private/no-store caching. File creation is compensated if the message
  transaction fails.
- `App\Actions\Chat\ServeChatMessageImage` is the only owner of private image
  delivery: fixed `local` disk, 404 on a null or missing path, `Content-Type`
  from `image_mime_type` falling back to `application/octet-stream`, forced
  `inline` + `nosniff`, and private/no-store caching. It never authorizes.
  Controllers own the ordering, and it is security-relevant: participant
  authorization runs before any file probe, so an absent file can never become
  an existence oracle for an outsider; the audit endpoint authorizes and records
  its permanent access *before* serving, so an authorized missing-file audit
  attempt is logged and then 404s.
- Reactions use the fixed 12-emoji `Reaction::ALLOWED` set. Each user has at
  most one reaction per message; only the peer may react (never the message
  sender). Add/replace/remove broadcasts realtime state but does not generate a
  notification.

## Feature toggle

- `ChatSettings::$chatEnabled` persists as `chat.chatEnabled` (spatie maps
  group.property, so the migration key must match the property name, not
  `chat.enabled`).
- `EnsureChatIsEnabled` closes only the user surface. Chat Settings and the
  Super Admin audit routes deliberately stay reachable while chat is off.
- `ChatSettings::$imageUploadsEnabled` persists as
  `chat.imageUploadsEnabled`; disabling it blocks new image uploads but does
  not hide or delete existing message images.
- `UpdateChatSettings` broadcasts the full chat/image-upload configuration via
  `ChatAvailabilityChanged` on private `chat.control` when either value moves.

## Larastan and paginators

- Declaring a return type for a paginator that has been mapped with
  `->through()` always fails Larastan: `through()` is annotated to return
  `static` with the original `TValue`, and `LengthAwarePaginator`'s templates
  are invariant, so the declared and inferred instantiations are rejected even
  when they print identically. Keep the mapped paginator in a local variable
  inside the controller action (no declared type, no closure return type) and
  hand it straight to `Inertia::scroll()`.

## Inertia scroll props

- The chat page serves the inbox and the transcript as two `Inertia::scroll()`
  props with distinct page names (`ChatController::CONVERSATIONS_PAGE` /
  `MESSAGES_PAGE`), so the two containers do not fight over one `page` query
  parameter. The transcript is paged newest-first and the client reverses it
  for display (`<InfiniteScroll data="messages" reverse>`).
- Switching conversation must pass `reset: ['messages']`, or the new
  conversation's page one merges into the previous transcript. The audit search
  needs the same `reset: ['conversations']`.
- The audit surface is fully paged, never capped: `chat/audit` uses the
  `conversations` page name plus an optional participant-name `search` (message
  bodies stay unsearchable), and `chat/audit/{conversation}` serves the
  transcript and its access log as two scroll props (`messages`, `logs`).
- Frontend surfaces and the realtime store: `mem:frontend/chat`.
