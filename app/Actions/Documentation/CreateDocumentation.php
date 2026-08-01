<?php

namespace App\Actions\Documentation;

use App\Models\Documentation;

class CreateDocumentation
{
    public function __construct(private readonly SaveDocumentation $saveDocumentation) {}

    /**
     * Store a new document.
     *
     * Slug uniqueness, searchable text, publication timestamp, and the position
     * inside the category are all owned by the shared persistence collaborator,
     * so creating and updating can never drift apart.
     *
     * @param  array{documentation_category_id: string, title: string, slug?: string|null, status: string, content: array<string, mixed>}  $attributes
     */
    public function handle(array $attributes): Documentation
    {
        return $this->saveDocumentation->handle($attributes);
    }
}
