<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateAvatarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
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
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(2048)->maxHeight(2048)->ratio(1)),
            ],
        ];
    }
}
