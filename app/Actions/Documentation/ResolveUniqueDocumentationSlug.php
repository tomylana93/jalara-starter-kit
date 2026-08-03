<?php

namespace App\Actions\Documentation;

use App\Models\Documentation;
use Illuminate\Support\Str;

final class ResolveUniqueDocumentationSlug
{
    /**
     * Resolve a unique slug for the documentation.
     */
    public function handle(string $value, Documentation $documentation): string
    {
        $base = Str::slug($value) ?: 'documentation';
        $slug = $base;
        $suffix = 2;

        while (
            Documentation::query()
                ->where('slug', $slug)
                ->when($documentation->exists, fn ($query) => $query->whereKeyNot($documentation->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
