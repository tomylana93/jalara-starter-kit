<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationReadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /*
             * Only the intent to follow the notification travels with the
             * request. The destination itself is read from the stored payload,
             * so no caller can turn this endpoint into an open redirect.
             */
            'open' => ['nullable', 'boolean'],
        ];
    }
}
