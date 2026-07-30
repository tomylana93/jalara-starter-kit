<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'maxFailedLoginAttempts' => ['required', 'integer', 'min:1', 'max:20'],
            'suspensionDurationMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'maintenanceEnabled' => ['required', 'boolean'],
        ];
    }
}
