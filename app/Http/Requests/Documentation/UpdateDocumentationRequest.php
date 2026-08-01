<?php

namespace App\Http\Requests\Documentation;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Support\DocumentationContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDocumentationRequest extends FormRequest
{
    use NormalizesDocumentationSlug;

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $this->merge(['content' => json_decode($this->string('content')->toString(), true)]);
        }

        $this->normalizeSlug();
    }

    public function authorize(): bool
    {
        $documentation = $this->route('documentation');

        return $documentation instanceof Documentation
            && ($this->user()?->can('update', $documentation) ?? false);
    }

    /**
     * @return array{documentation_category_id: string, title: string, slug: string|null, status: string, content: array<string, mixed>}
     */
    public function documentationAttributes(): array
    {
        return [
            'documentation_category_id' => $this->string('documentation_category_id')->toString(),
            'title' => $this->string('title')->toString(),
            'slug' => $this->filled('slug') ? $this->string('slug')->toString() : null,
            'status' => $this->string('status')->toString(),
            'content' => $this->array('content'),
        ];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Documentation $documentation */
        $documentation = $this->route('documentation');

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'alpha_dash:ascii',
            Rule::unique(Documentation::class)->ignore($documentation),
        ];

        if ($documentation->published_at !== null) {
            $slugRules[] = Rule::in([$documentation->slug]);
        }

        return [
            'documentation_category_id' => ['required', 'uuid', 'exists:documentation_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => $slugRules,
            'status' => ['required', Rule::enum(DocumentationStatus::class)],
            'content' => ['required', 'array'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $content = $this->input('content');

            if (is_array($content)) {
                DocumentationContent::validate($content, $validator);
            }
        }];
    }
}
