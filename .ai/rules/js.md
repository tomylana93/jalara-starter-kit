---
paths:
  - 'resources/js/**/*.test.ts'
  - 'resources/js/**'
---

# Js

## Pin the timezone in tests that render instants
There is no globally configured test timezone. A test that renders a browser-local instant must pin the zone itself with `withTimeZone(TEST_TIME_ZONE, () => ...)` from `@/test/timeZone`, wrapping the mount as well as the assertions, because formatting happens during render. `formatBrowserDateTime` passes no `timeZone` to `Intl` on purpose, so an unpinned assertion silently follows the machine's clock and passes or fails depending on where it runs.

## Never pair a back()-redirecting action with a second Inertia visit
Firing `router.patch(action)` and `router.visit(destination)` from one handler races: the action's `back()` answers 303, the browser follows it with a GET of the *current* page, and that response lands after the destination's and overwrites it. The user sees the target flicker and stay put; a second click "works" only because the first already changed the state that guarded the action. Vitest cannot catch this — mocked `router` calls both look fine. Send one request and let the server redirect where it should go, passing an intent flag (never a URL — a client-supplied redirect target is an open redirect). See `NotificationController::markAsRead` and its `open` flag.

## Give lucide icons an explicit color over brand surfaces
`app.css` paints every `svg.lucide` with `var(--primary)` and only releases it back to `inherit` inside an ancestor whose class attribute carries a whole token like `text-primary-foreground`.

A foreground applied through an arbitrary variant (e.g. the bubble's `*:data-[slot=bubble-content]:text-primary-foreground`) is a different token, so `[class~=...]` never matches it and the icon stays brand-colored — invisible on a `bg-primary` surface.

Put `text-current` on any lucide icon rendered over a brand-painted surface. The whole rule block is wrapped in `:where()`, so one explicit utility wins. The failure renders as invisible ink, not an error, so assert the class in a test.

## jsdom force-mounts dropdown content, so a closed-menu assertion proves nothing
Under jsdom, `DropdownMenuContent` is force mounted, so a menu item is findable without ever opening the menu. A test that asserts an item is absent therefore proves nothing about authorization gating. Assert on the trigger's presence instead.
