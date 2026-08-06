<?php

namespace App\Http\Requests\Settings;

use App\Data\Settings\UpdateSecuritySettingsData;
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

    /**
     * The validated settings, with every value already in its final type.
     */
    public function toData(): UpdateSecuritySettingsData
    {
        return new UpdateSecuritySettingsData(
            maxFailedLoginAttempts: (int) $this->validated('maxFailedLoginAttempts'),
            suspensionDurationMinutes: (int) $this->validated('suspensionDurationMinutes'),
            maintenanceEnabled: $this->boolean('maintenanceEnabled'),
        );
    }
}
