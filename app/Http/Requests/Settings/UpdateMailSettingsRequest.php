<?php

namespace App\Http\Requests\Settings;

use App\Data\Settings\UpdateMailSettingsData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fromName' => ['required', 'string', 'max:100'],
            'fromAddress' => ['required', 'string', 'email', 'max:254'],
        ];
    }

    /**
     * The validated settings, with every value already in its final type.
     */
    public function toData(): UpdateMailSettingsData
    {
        return new UpdateMailSettingsData(
            fromName: (string) $this->validated('fromName'),
            fromAddress: (string) $this->validated('fromAddress'),
        );
    }
}
