<?php

namespace Database\Factories;

use App\Enums\BrandingAsset;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImageUpload>
 */
class ImageUploadFactory extends Factory
{
    protected $model = ImageUpload::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target' => ImageUploadTarget::Avatar,
            'target_key' => null,
            'status' => ImageUploadStatus::Pending,
            'source_path' => ImageUpload::SOURCE_DIRECTORY.'/'.fake()->uuid().'.png',
            'source_mime_type' => 'image/png',
            'result_path' => null,
            'result_mime_type' => null,
            'lock_key' => null,
            'payload' => null,
            'error_code' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Take the active-target lock the same way the intake action does.
     */
    public function locked(): static
    {
        return $this->afterMaking(function (ImageUpload $upload): void {
            $upload->lock_key = ImageUpload::lockKeyFor(
                $upload->target,
                (string) $upload->user_id,
                $upload->target_key,
            );
        });
    }

    public function branding(BrandingAsset $asset = BrandingAsset::Logo): static
    {
        return $this->state(fn (): array => [
            'target' => ImageUploadTarget::Branding,
            'target_key' => $asset->value,
        ]);
    }

    public function chatImage(): static
    {
        return $this->state(fn (): array => [
            'target' => ImageUploadTarget::ChatImage,
            'target_key' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (): array => ['status' => ImageUploadStatus::Processing]);
    }

    public function ready(string $resultPath = 'avatars/example.webp'): static
    {
        return $this->state(fn (): array => [
            'status' => ImageUploadStatus::Ready,
            'result_path' => $resultPath,
            'result_mime_type' => 'image/webp',
            'lock_key' => null,
            'completed_at' => now(),
        ]);
    }

    public function failed(string $errorCode = 'processing_failed'): static
    {
        return $this->state(fn (): array => [
            'status' => ImageUploadStatus::Failed,
            'error_code' => $errorCode,
            'lock_key' => null,
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => ImageUploadStatus::Cancelled,
            'lock_key' => null,
            'completed_at' => now(),
        ]);
    }
}
