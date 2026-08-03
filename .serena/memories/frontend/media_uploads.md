# Media Upload UI

## Async lifecycle

- Uploads no longer travel as Inertia visits. `resources/js/lib/imageUploads.ts`
  owns the transport (Inertia's standalone HTTP client, so CSRF and interceptors
  still apply) and the polling state machine; the server contract lives in
  `mem:backend/media_uploads`.
- The navigation guard covers the **byte transfer only**. It is released as soon
  as the server answers `202`; from there the work is server-side and leaving the
  page costs nothing. Progress percentages therefore exist only while
  `uploading` — the `processing` state has no measurable percentage and must not
  render a progress bar.
- Polling backs off 1s -> 5s and stops after 10 minutes. A stop is *not* a
  failure: it never marks the job failed, and the UI offers "check again" against
  the same record rather than re-uploading.
- The stored image stays on screen until an upload reports `ready`; a failure or
  cancellation restores it untouched. Local object-URL preview stands in
  meanwhile.
- `useResumableUploads()` fetches active uploads once per page and hands each
  field its own via the `resume` prop, so a reload picks up work in flight
  instead of showing a stale image. Chat does the same through
  `restorePendingImage()`.
- A `409` is adopted only when the server actually hands back a record. It does
  so for the requester's own upload (another tab); for another administrator's
  branding upload the body carries a message and nothing to poll, and the field
  must stay in its conflict state rather than polling into a 403.
- `useChat().sendMessage()` resolves at **acceptance**, never at publication. It
  returns `{accepted, message, settled}`: `message` is set only for the
  synchronous text path, and `settled` is the background watcher a surface may
  optionally await (chat page uses it to navigate to a conversation a first
  image message opened). Awaiting the watcher inside `sendMessage` is the bug
  this shape exists to prevent — it pins `state.sending` and the navigation
  guard to queue work that no longer needs the page.
- Pending chat uploads live in `state.pendingImageUploads`, a list keyed by
  upload id. Several may be in flight at once, so a single-record field would
  let the second erase the first; `cancelPendingImage(id?)` cancels the named
  upload, defaulting to the oldest.
- `error_code` values map to `media.upload.error.*` keys; unknown codes fall back
  rather than being displayed raw. `useChat` stores translation *keys* in
  `state.error`, not translated strings.

- Keep the installed shadcn-vue `AspectRatio` and `Progress` primitives under `resources/js/components/ui`; they are approved registry additions. The read-only registry rule in `mem:frontend/core` governs how they may be changed.
- Image upload controls belong inside the page's primary form structure; rendering them as a separate section outside that form is a defect. They may still submit independently to dedicated POST/DELETE endpoints, but must not introduce nested `<form>` elements or leak their file input into the primary settings submission.
- Upload fields intentionally have no helper/description text. Render only the label, preview, active status/progress, validation error, and actions. Do not render passive `Saved` or `No image selected` footer text; hide the status footer entirely while idle or done.
- Every visible icon-only upload action must retain an `aria-label` and use the installed shadcn-vue Tooltip with matching localized action text.
- The only image metadata shown is human-readable file size. Read it from the selected `File` immediately and use a best-effort `HEAD` request (`Content-Length`) for an already-stored URL; hide it when unavailable.
- Branding icon light/dark previews use the same wide 3:1 layout as branding logos. Only the Profile avatar remains circular. Empty branding previews, including icons, show `No image` inside the media area.
- Branding must always expose upload controls for logo light/dark, icon light/dark, and auth split background; the auth-background uploader must not disappear merely because the currently selected or persisted auth layout is not `split`.
- Uploaded branding images fall back to project assets in `public/assets/images`, never Laravel starter artifacts: `branding/logo.png`, `branding/logo-dark.png`, `branding/icon.png`, `branding/icon-dark.png`, `branding/favicon.ico`, and `auth-bg.jpg`.
- Central brand rendering must resolve uploaded asset -> matching `public/assets/images` fallback. Do not fall back to `AppLogoIcon` or the Laravel starter favicon assets.
- Image upload fields must compose the CLI-generated shadcn-vue Attachment primitives and use the package-provided shimmer utility through `shadcn-vue/tailwind.css`; do not reproduce either locally.
- The Attachment compatibility path (see the registry rule in `mem:frontend/core`) is the registry-updated Button with `icon-xs` plus global `Primitive` registration in `resources/js/app.ts`.
- Upload wrappers fill their grid cell and compose one full-width vertical Attachment tile. Preview/media is the primary area; status and progress share the tile footer; upload, retry, and remove actions overlay the preview; the full-card trigger remains independently clickable behind those actions. Render validation errors as siblings after Attachment, never inside its action area.
- Consumer layout must neutralize AttachmentMedia's registry `aspect-square` and centering. Wide previews use their supplied AspectRatio and align to the start; circular previews use their intrinsic avatar height and stay centered within the media area. Without these consumer overrides, Branding and Profile render large empty square rows.
- Use a native hidden `<input type="file">` inside the upload consumer rather than the registry Input component. Registry Input uses `v-model`, which causes Vue `InvalidStateError` when it attempts to write a selected filename back to a file input after validation responses.