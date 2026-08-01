<?php

namespace App\Http\Requests\Documentation;

use Illuminate\Support\Str;

/**
 * Normalizes the submitted slug before validation so the uniqueness rule runs
 * against the value that will actually be persisted.
 *
 * A blank slug becomes `null`, which lets the backend derive one from the
 * title. A filled slug is slugified; when slugification yields nothing usable
 * the raw input is kept so the format rule rejects it instead of silently
 * falling back to the generated slug.
 */
trait NormalizesDocumentationSlug
{
    protected function normalizeSlug(): void
    {
        if (! $this->filled('slug')) {
            $this->merge(['slug' => null]);

            return;
        }

        $raw = $this->string('slug')->toString();
        $slug = Str::slug($raw);

        $this->merge(['slug' => $slug === '' ? $raw : $slug]);
    }
}
