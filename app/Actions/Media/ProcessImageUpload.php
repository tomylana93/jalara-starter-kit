<?php

namespace App\Actions\Media;

use App\Exceptions\Media\StoredImageWriteFailed;
use App\Models\ImageUpload;
use Illuminate\Support\Facades\Image;

/**
 * Turns a staged upload into the image the application will actually serve.
 *
 * Two rules shape the output. Format is adaptive rather than uniform: PNG stays
 * PNG so flat artwork keeps its lossless edges, while JPEG and WebP both leave
 * as WebP. Geometry only ever shrinks — the image is auto-oriented from its EXIF
 * data and then scaled down inside the target's box, never cropped and never
 * enlarged, so a small upload is published exactly as it arrived.
 */
final class ProcessImageUpload
{
    /**
     * The quality every WebP output is encoded at.
     */
    public const int WEBP_QUALITY = 80;

    /**
     * Process the staged bytes and store the result.
     *
     * @return array{path: string, mime_type: string}
     */
    public function handle(ImageUpload $upload, string $directory): array
    {
        [$maxWidth, $maxHeight] = $upload->maxDimensions();

        $image = Image::fromStorage($upload->source_path, ImageUpload::SOURCE_DISK)
            ->orient()
            ->scale($maxWidth, $maxHeight);

        $image = $upload->source_mime_type === 'image/png'
            ? $image->toPng()
            : $image->toWebp()->quality(self::WEBP_QUALITY);

        $disk = $upload->target->disk();
        $path = $image->store($directory, $disk);

        throw_if($path === false, StoredImageWriteFailed::class, $directory);

        return ['path' => $path, 'mime_type' => $image->mimeType()];
    }
}
