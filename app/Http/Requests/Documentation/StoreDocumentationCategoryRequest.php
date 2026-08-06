<?php

namespace App\Http\Requests\Documentation;

use App\Data\Documentation\DocumentationCategoryData;
use App\Models\DocumentationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DocumentationCategory::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique(DocumentationCategory::class)]];
    }

    /**
     * The validated category attributes.
     */
    public function toData(): DocumentationCategoryData
    {
        return new DocumentationCategoryData(
            name: $this->string('name')->toString(),
        );
    }
}
