# Notifications & Broadcasting

## Payload contract

- Database and broadcast channels share one shape so a single client type renders
  history and realtime arrivals: `type`, `title`, `message`, `url` (nullable).
- `type` is a stable semantic slug, never a PHP class name. Laravel merges
  `broadcastType()` over the payload's `type` key
  (`BroadcastNotificationCreated::broadcastWith()` does
  `array_merge($data, ['id' => ..., 'type' => $this->broadcastType()])`), so
  `toDatabase()['type']` and `broadcastType()` must return the same value or the
  two channels silently disagree.
- `toBroadcast()` additionally inlines `created_at` and `read_at`: a broadcast has
  no database row for the client to read them from.
- `App\Http\Presenters\NotificationPresenter` owns the record → client mapping;
  both the controller and `HandleInertiaRequests` go through it.

## Pagination & Querying

- `App\Actions\Notifications\PaginateNotifications` handles notification-history querying: relation selection (all vs unread), chat-toggle exclusion, deterministic `created_at desc, id desc` ordering, count reuse, fixed 10-row pagination, and clamping of out-of-range pages to the last available page.
- `App\Http\Presenters\NotificationPresenter::presentPage` maps the resulting `LengthAwarePaginator` to the `{data, meta}` shape consumed by Inertia.

## Ordering

- Notification ids are UUIDv4 (`Str::uuid()` in `NotificationSender`), NOT the
  UUIDv7 used by application models, so the id is not time-sortable.
- `notifications()` / `unreadNotifications()` already apply `latest()`, which ties
  whenever several notifications land in the same second. Add `orderBy('id',
  'desc')` as a tie-breaker for deterministic paging; without it a row can repeat
  or vanish between pages. Tests that assert "newest first" must `travel()`
  between sends, since same-second inserts have no meaningful order.

## Channel authorization

- `Broadcast::channel()` resolves the DEFAULT driver at call time
  (`BroadcastManager::__call` → `$this->driver()`). Channels registered while
  booting therefore belong to whichever broadcaster was default then — the `null`
  broadcaster under phpunit. Switching `broadcasting.default` inside a test yields
  a fresh broadcaster with zero channels, so every request answers 403 and
  authorization assertions pass for the wrong reason. Re-`require
  base_path('routes/channels.php')` after switching.
- The channel callback must compare UUIDs as strings. `install:broadcasting`
  generates `(int) $user->id === (int) $id`, which collapses every UUID to 0 and
  authorizes any user onto any other user's channel. Always review that file after
  running the installer.

## Shared Inertia prop

- The bell state is shared as `notificationBell`, deliberately not
  `notifications`: a page prop of the same key overrides the shared one, which
  would leave the bell stateless on the notification page itself.

## Processes

- `ShouldQueue` notifications need a queue worker to broadcast.
  `DevCommands::registerDefaults()` already runs `serve`, `queue:listen`, `pail`,
  and `vite`; Reverb self-registers `reverb:start` via `ReverbServiceProvider`, so
  `composer run dev` needs no wiring for either.
- E2E process/port isolation: `mem:testing/browser`.
