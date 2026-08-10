<?php

namespace App\Http\Requests\Documentation;

use App\Models\Documentation;
use App\Support\DocumentationContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreDocumentationImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Documentation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `File::image()` inspects the bytes rather than trusting the declared
     * type, and the explicit `mimetypes` rule pins the three formats the
     * processing step knows how to re-encode.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                File::image()
                    ->extensions(['png', 'jpg', 'jpeg', 'webp'])
                    ->rules('mimetypes:image/png,image/jpeg,image/webp')
                    ->max(DocumentationContent::IMAGE_MAX_KILOBYTES)
                    ->dimensions(
                        Rule::dimensions()
                            ->maxWidth(DocumentationContent::IMAGE_MAX_DIMENSION)
                            ->maxHeight(DocumentationContent::IMAGE_MAX_DIMENSION),
                    ),
            ],
        ];
    }
}
