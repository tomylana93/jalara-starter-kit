<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowConversationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `before` is the oldest message the client already holds; the window
     * answers with what precedes it, which is how the infinite scroll walks
     * upward without skipping or repeating a row.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'before' => ['nullable', 'uuid'],
        ];
    }
}
