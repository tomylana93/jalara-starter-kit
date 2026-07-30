<?php

namespace App\Http\Requests\Settings;

use App\Enums\BrandingAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\ImageFile;

class UpdateBrandingAssetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', $this->imageRule()],
        ];
    }

    /**
     * The asset resolved from the route parameter.
     */
    public function asset(): BrandingAsset
    {
        $asset = $this->route('asset');

        return $asset instanceof BrandingAsset
            ? $asset
            : BrandingAsset::from((string) $asset);
    }

    /**
     * Build the size and dimension rule for the asset being uploaded.
     *
     * SVG is deliberately absent: it can carry script, and these files are
     * served from the same origin as the application.
     */
    private function imageRule(): ImageFile
    {
        $rule = File::image()
            ->extensions(['png', 'jpg', 'jpeg', 'webp'])
            ->rules('mimetypes:image/png,image/jpeg,image/webp');

        return match ($this->asset()) {
            BrandingAsset::Logo, BrandingAsset::LogoDark => $rule
                ->max(2 * 1024)
                ->dimensions(Rule::dimensions()->maxWidth(2400)->maxHeight(800)),
            BrandingAsset::Icon, BrandingAsset::IconDark => $rule
                ->max(2 * 1024)
                ->dimensions(Rule::dimensions()->maxWidth(2048)->maxHeight(2048)->ratio(1)),
            BrandingAsset::AuthBackground => $rule
                ->max(5 * 1024)
                ->dimensions(Rule::dimensions()->maxWidth(3840)->maxHeight(2160)),
        };
    }
}
