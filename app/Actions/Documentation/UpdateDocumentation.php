<?php

namespace App\Actions\Documentation;

use App\Models\Documentation;

class UpdateDocumentation
{
    public function __construct(private readonly SaveDocumentation $saveDocumentation) {}

    /**
     * Apply the editor changes to an existing document.
     *
     * A document that has already been published keeps its slug; that freeze
     * lives in the shared persistence collaborator together with the rest of
     * the write rules.
     *
     * @param  array{documentation_category_id: string, title: string, slug?: string|null, status: string, content: array<string, mixed>}  $attributes
     */
    public function handle(Documentation $documentation, array $attributes): Documentation
    {
        return $this->saveDocumentation->handle($attributes, $documentation);
    }
}
