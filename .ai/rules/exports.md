---
paths:
  - 'app/Exports/**'
---

# Exports

## A PDF cannot rely on the browser owning the timezone
Everywhere else instants cross as UTC ISO 8601 and `formatBrowserDateTime` renders them in the viewer's zone. A PDF has no such browser: Chromium runs on the server, in the server's zone. The reader's IANA zone therefore arrives as a validated request input (`timezone`, rule `timezone`, falling back to UTC) and `App\Support\InstantFormatter` does the formatting in PHP.

That is a second implementation of one rule in a second language. Both read `tests/Fixtures/instants.json`; changing either formatter alone turns one suite red. Never add an expectation to only one side.

The spreadsheet keeps writing native UTC date cells instead — it is a data file, not a picture of a screen.
