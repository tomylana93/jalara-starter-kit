<?php

namespace App\Actions\Documentation;

use App\Data\Documentation\DocumentationData;
use App\Models\Documentation;

final readonly class UpdateDocumentation
{
    public function __construct(private SaveDocumentation $saveDocumentation) {}

    /**
     * Apply the editor changes to an existing document.
     *
     * A document that has already been published keeps its slug; that freeze
     * lives in the shared persistence collaborator together with the rest of
     * the write rules.
     */
    public function handle(Documentation $documentation, DocumentationData $data): Documentation
    {
        return $this->saveDocumentation->handle($data, $documentation);
    }
}
