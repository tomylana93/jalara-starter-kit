---
paths:
  - 'resources/views/pdf/**'
---

# Pdf

## PDF templates inline their own print stylesheet
Styles come from `resources/css/pdf.css`, a separate Vite entry, passed in as view data by the export class and inlined into `<style>`. Never `<link>` it: Browsershot renders from an HTML string, so a link sends Chromium back to the application and the document silently arrives unstyled. Never reuse `app.css`, which carries dark-mode tokens and the configurable branding theme.

`spatie/laravel-pdf` defaults to Letter — set A4 explicitly.

Header and footer views render in their own document and inherit no CSS from the page, so anything placed there needs its own styles. Keep the identity block in the body of the main view for that reason.

The stylesheet lookup lives behind `App\Support\Documents\DocumentStylesheet` because `withoutVite()` does not stub `Vite::content()` and the Pest job builds no assets; tests bind a stub.
