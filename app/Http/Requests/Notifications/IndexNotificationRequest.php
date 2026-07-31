<?php

namespace App\Http\Requests\Notifications;

use App\Http\Controllers\NotificationController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'string', Rule::in(NotificationController::FILTERS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
