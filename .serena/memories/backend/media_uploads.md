# Queued Image Uploads

## Intake contract

- Every *new* user image (avatar, the five branding assets, chat images) is
  accepted, never applied in-request. The endpoint validates as before, stages
  the untouched bytes on the private `local` disk under `image-uploads/` with a
  generated name, opens an `image_uploads` row, dispatches a job, and answers
  JSON `202` carrying `poll_url`/`cancel_url`.
- Text-only chat keeps the synchronous `201` contract. Only image-bearing
  requests become asynchronous.
- A first chat message carrying an image does **not** open its conversation in
  the controller. The job creates conversation *and* message together on
  success, so a failed upload cannot leave an empty conversation behind. This
  keeps `SendMessage` the only conversation writer.
- `SendMessage` takes an already-stored `$imagePath`/`$imageMimeType`; it no
  longer stores files. The queue owns that step.

## Lifecycle and locking

- `ImageUploadStatus`: pending/processing/ready/failed/cancelled. Terminal rows
  are kept `ImageUpload::RETENTION_HOURS` (24) then removed by `MassPrunable`.
- The active-target lock is the **unique `lock_key` column**, not a read-check:
  `avatar:{userId}`, `branding:{asset}` (deliberately global — two admins must
  not race one logo), and NULL for chat so several pending messages may each
  carry an image. NULLs are non-conflicting on every supported driver. The key
  is cleared on every terminal transition. A conflict answers `409`, but the
  blocking upload is only handed back when the requester **owns** it. Branding
  is shared, so another administrator gets `409` with a message and no
  `poll_url`/`cancel_url` — those endpoints are owner-only and would answer 403.
- Claim, cancel, and fail are all conditional `UPDATE ... WHERE status active`,
  so a cancellation landing beside a worker resolves to exactly one winner and
  a job giving up can never overwrite a cancellation.

## Processing invariants

- Requires `intervention/image` (Laravel's Image API is inert without it);
  driver is GD via the `images.default` default.
- PNG stays PNG; JPEG and WebP both output WebP at quality 80
  (`ProcessImageUpload::WEBP_QUALITY`).
- `orient()->scale(w, h)` only — `scale()` maps to Intervention `scaleDown`, so
  output never crops and never upscales. Boxes: avatar/icon 512x512, logo
  1200x400, auth background 1920x1080, chat 1600x1600. These are display bounds,
  independent of the larger input limits the Form Requests enforce.
- Authorization is checked at intake **and re-checked immediately before
  publication**, because a permission or account status can change while queued.
  A result that may no longer be published is deleted, not left for the sweep.
- The existing asset keeps serving until the replacement path is **committed**
  (`SwapStoredImagePath`, the queue-side counterpart to `ReplaceStoredImage`).
  The previous file is deleted via `DB::afterCommit`, never merely after the
  write: inside a transaction the pointer is not durable yet, and a rollback
  would restore a value naming a file already deleted. Failure or cancellation
  never removes what was already there.
- Jobs: 3 attempts, backoff `[1,5,10]`, timeout 60s — deliberately under the
  database queue's `retry_after=90` so a slow job is never double-claimed.
- Existing stored images are never reprocessed or backfilled.

## Finalization boundary

- Publishing to the domain and transitioning the upload to `ready` are **one
  transaction**, in `ProcessQueuedImageUpload::handle`. Either half alone is
  wrong: an applied image on a still-`processing` upload gets republished by the
  next attempt, and a `ready` upload whose target never changed points clients
  at nothing.
- `CompleteImageUpload` is conditional on status `processing` and returns bool.
  Refusal means a cancellation won mid-flight; the job throws
  `ImageUploadNoLongerPublishable` so the publication rolls back with it.
  Returning false without throwing would commit the publication — the throw is
  load-bearing, not decorative.
- It touches no storage. The staged source is the only copy and is deleted by
  the job **after** commit, so a rolled-back attempt still has something to
  retry from.
- Retries are therefore idempotent by construction: a part-way attempt leaves
  nothing behind, and a redelivered finished job fails to claim (`active()`
  excludes terminal states) and returns.
- `SendMessage` defers its broadcast and notification to `DB::afterCommit`.
  Called from the queue it sits inside the job's transaction, and announcing
  from in there would show clients a message a rollback could erase. Outside a
  transaction the callback runs immediately, so text-only chat is unchanged.

## Orphan sweep

- `images:prune-orphans` is dry-run by default; `--delete` and `--older-than`
  (hours, default 24). Scheduled daily with `--delete` plus `model:prune`, both
  `withoutOverlapping`.
- The command is only the CLI adapter. `App\Actions\Media\PruneOrphanImages`
  owns managed-prefix traversal, reference protection, grace-period and symlink
  protection, and optional deletion, returning `PruneOrphanImagesResult`
  (per-disk `candidates`/`deleted`/`skipped`, always one entry per managed
  disk). Negative `--older-than` clamps to zero in the Action, not the command.
- Managed prefixes only: public `avatars/`, `branding/`; local `chat/`,
  `image-uploads/`. Never touches anything else.
- It walks the directory itself rather than using `Storage::allFiles()`:
  Flysystem's default link policy **throws** on listing a directory containing a
  symlink, which would abort the whole sweep. The walk skips links and
  directories outright.
- Protected: referenced paths (users, branding settings, messages, non-terminal
  sources, any recorded `result_path`), anything younger than the grace period,
  symlinks, directories, and everything outside the managed prefixes.

## Status surface

- `routes/media.php` — owner-only index/show/destroy behind `auth` alone,
  matching the least restrictive upload it reports on; ownership enforced by
  `ImageUploadPolicy`.
- `ImageUploadResource` is the only view a client gets. The staging path, the
  encrypted `payload`, and `lock_key` never leave the server; `error_code` is a
  translation key, never an exception message.
- A ready chat upload records the created message id in `target_key`, which the
  `resultMessage` belongs-to relation resolves. `ImageUploadResource` runs **no
  queries**: it renders the message/conversation only when the relation is
  already loaded, so an unloaded or deleted result presents as `null`. The
  controller preloads `resultMessage.reactions` and
  `resultMessage.conversation.participants.user.roles` for ready chat uploads *after*
  `ImageUploadPolicy` authorization — never before, and never for the index,
  whose active uploads can have no result. Participant roles are load-bearing
  because `ChatPresenter::profile()` calls Spatie `getRoleNames()`, which
  otherwise queries through `loadMissing('roles')` during Resource serialization.

Frontend state machine and polling: `mem:frontend/media_uploads`.
