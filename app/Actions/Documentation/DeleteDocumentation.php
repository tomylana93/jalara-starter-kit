<?php

namespace App\Actions\Documentation;

use App\Models\Documentation;

final class DeleteDocumentation
{
    /**
     * Permanently remove a document.
     *
     * Documentation keeps no revision history, so the record is gone for good.
     */
    public function handle(Documentation $documentation): void
    {
        $documentation->delete();
    }
}
