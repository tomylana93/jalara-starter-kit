---
paths:
  - 'resources/js/**/*.test.ts'
---

# Js

## Pin the timezone in tests that render instants
There is no globally configured test timezone. A test that renders a browser-local instant must pin the zone itself with `withTimeZone(TEST_TIME_ZONE, () => ...)` from `@/test/timeZone`, wrapping the mount as well as the assertions, because formatting happens during render. `formatBrowserDateTime` passes no `timeZone` to `Intl` on purpose, so an unpinned assertion silently follows the machine's clock and passes or fails depending on where it runs.
