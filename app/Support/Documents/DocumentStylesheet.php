<?php

namespace App\Support\Documents;

use Illuminate\Support\Facades\Vite;

/**
 * The print stylesheet, read from the built bundle.
 *
 * PDF templates inline their CSS rather than linking it: Browsershot renders
 * from an HTML string, so a `<link>` would send Chromium back to the
 * application for the file - the usual reason a document arrives unstyled in
 * production, and one that fails silently.
 *
 * Reading the bundle is a real dependency on `pnpm run build` having run, and
 * `Vite::content` says so loudly rather than returning nothing. It lives behind
 * this class so that dependency can be swapped: `withoutVite()` does not cover
 * `content()`, and the Pest job deliberately carries no Node and builds no
 * assets, so a test asserting document markup binds a stub instead.
 *
 * Left open for exactly that reason. It is a seam, not a value object.
 */
class DocumentStylesheet
{
    public function contents(): string
    {
        return Vite::content('resources/css/pdf.css');
    }
}
