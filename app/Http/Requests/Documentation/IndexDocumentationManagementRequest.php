<?php

namespace App\Http\Requests\Documentation;

use App\Tables\DocumentationTable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the management list query.
 *
 * The page is the only part of the listing the client may negotiate; the
 * ordering, the page size, and the absence of a search term are fixed by
 * {@see DocumentationTable}. Authorization stays in the controller,
 * which gates the whole management surface through the documentation policy.
 */
class IndexDocumentationManagementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
