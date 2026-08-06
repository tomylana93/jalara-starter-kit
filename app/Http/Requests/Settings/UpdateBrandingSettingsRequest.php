<?php

namespace App\Http\Requests\Settings;

use App\Data\Settings\UpdateBrandingSettingsData;
use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPairPreset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandingSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'companyName' => ['required', 'string', 'max:100'],
            'footerText' => ['nullable', 'string', 'max:500'],
            'identityMode' => ['required', 'string', Rule::enum(BrandingIdentityMode::class)],
            'authLayout' => ['required', 'string', Rule::enum(AuthLayoutPreset::class)],
            'appLayout' => ['required', 'string', Rule::enum(AppLayoutPreset::class)],
            'colorTheme' => ['required', 'string', Rule::enum(ColorThemePreset::class)],
            'fontPair' => ['required', 'string', Rule::enum(FontPairPreset::class)],
        ];
    }

    /**
     * The validated branding, with every preset already resolved to its enum.
     */
    public function toData(): UpdateBrandingSettingsData
    {
        $footerText = $this->validated('footerText');

        return new UpdateBrandingSettingsData(
            companyName: (string) $this->validated('companyName'),
            footerText: $footerText === null ? null : (string) $footerText,
            identityMode: BrandingIdentityMode::from((string) $this->validated('identityMode')),
            authLayout: AuthLayoutPreset::from((string) $this->validated('authLayout')),
            appLayout: AppLayoutPreset::from((string) $this->validated('appLayout')),
            colorTheme: ColorThemePreset::from((string) $this->validated('colorTheme')),
            fontPair: FontPairPreset::from((string) $this->validated('fontPair')),
        );
    }
}
