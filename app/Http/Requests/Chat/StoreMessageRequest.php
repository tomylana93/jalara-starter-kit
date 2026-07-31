<?php

namespace App\Http\Requests\Chat;

use App\Models\Chat\Message;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * A message names either the conversation it belongs to or the recipient it
     * opens one with, never both: the conversation is created lazily by the
     * first valid message.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required_without:recipient_id', 'missing_with:recipient_id', 'uuid'],
            'recipient_id' => ['required_without:conversation_id', 'uuid'],
            /*
             * Multiline is allowed; whitespace-only is not, which `string` alone
             * would accept.
             */
            'body' => ['required', 'string', 'max:'.Message::MAX_LENGTH],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $body = $this->input('body');

        if (is_string($body)) {
            $this->merge(['body' => trim($body)]);
        }
    }
}
