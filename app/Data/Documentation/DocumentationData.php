<?php

namespace App\Data\Documentation;

use App\Enums\DocumentationStatus;

final readonly class DocumentationData
{
    /**
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public string $documentationCategoryId,
        public string $title,
        public ?string $slug,
        public DocumentationStatus $status,
        public array $content,
    ) {}
}
