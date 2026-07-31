<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexAuditRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Only participants are searchable. Searching the contents of direct
     * messages stays deliberately out of the audit surface.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'conversations' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
