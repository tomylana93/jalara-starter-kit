<?php

namespace App\Http\Requests\Settings;

use App\Data\Settings\UpdateGeneralSettingsData;
use App\Enums\DateFormat;
use App\Enums\Locale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'applicationName' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'defaultLocale' => ['required', 'string', Rule::enum(Locale::class)],
            'dateFormat' => ['required', 'string', Rule::enum(DateFormat::class)],
        ];
    }

    /**
     * The validated settings, with every value already in its final type.
     */
    public function toData(): UpdateGeneralSettingsData
    {
        $description = $this->validated('description');

        return new UpdateGeneralSettingsData(
            applicationName: (string) $this->validated('applicationName'),
            description: $description === null ? null : (string) $description,
            defaultLocale: Locale::from((string) $this->validated('defaultLocale')),
            dateFormat: DateFormat::from((string) $this->validated('dateFormat')),
        );
    }
}
