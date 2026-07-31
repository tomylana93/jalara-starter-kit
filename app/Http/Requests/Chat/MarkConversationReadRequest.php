<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkConversationReadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The client names the newest message it actually rendered, so a receipt
     * reflects what was seen rather than what merely arrived.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message_id' => ['required', 'uuid'],
        ];
    }
}
