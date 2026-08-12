---
paths:
  - 'app/Models/**'
---

# Models

## No one-of-many relations on UUID keys
`latestOfMany()` / `oldestOfMany()` / `ofMany()` cannot be used in this codebase. Laravel builds them from a subquery that always adds the primary key as a tiebreaker aggregate — `CanBeOneOfMany::ofMany()` injects `$columns[$keyName] = 'MAX'` even when you pass another sort column — and PostgreSQL has no `MAX` for the `uuid` type. Every model here has a UUIDv7 key, so the relation fails outright with "function max(uuid) does not exist". Laravel documents this as a hard limitation, not a tuning problem.

Maintain a pointer column instead and make the relation a `belongsTo`. `Conversation::latestMessage()` is the worked example: `SendMessage` writes `last_message_id` next to `last_message_at` in the same transaction.
