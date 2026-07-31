<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChatPageContextRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The identifier names one open page instance. It is scoped to the
     * authenticated user server-side, so it carries no authority of its own and
     * only has to be an opaque, bounded token.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'context' => ['required', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }
}
