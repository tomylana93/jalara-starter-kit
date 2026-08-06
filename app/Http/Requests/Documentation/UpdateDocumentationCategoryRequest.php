<?php

namespace App\Http\Requests\Documentation;

use App\Data\Documentation\DocumentationCategoryData;
use App\Models\DocumentationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('documentationCategory');

        return $category instanceof DocumentationCategory
            && ($this->user()?->can('update', $category) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var DocumentationCategory $category */
        $category = $this->route('documentationCategory');

        return ['name' => ['required', 'string', 'max:255', Rule::unique(DocumentationCategory::class)->ignore($category)]];
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
