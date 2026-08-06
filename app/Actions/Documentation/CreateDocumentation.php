<?php

namespace App\Actions\Documentation;

use App\Data\Documentation\DocumentationData;
use App\Models\Documentation;

final readonly class CreateDocumentation
{
    public function __construct(private SaveDocumentation $saveDocumentation) {}

    /**
     * Store a new document.
     *
     * Slug uniqueness, searchable text, publication timestamp, and the position
     * inside the category are all owned by the shared persistence collaborator,
     * so creating and updating can never drift apart.
     */
    public function handle(DocumentationData $data): Documentation
    {
        return $this->saveDocumentation->handle($data);
    }
}
