<?php

namespace App\Actions\Chat;

use App\Models\Chat\Message;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a message's private image from the local disk.
 *
 * Delivery only: the caller has already decided that this request may see the
 * image, so the file is never probed before authorization has passed and an
 * absent file can never answer a question the policy refused.
 */
final readonly class ServeChatMessageImage
{
    public function __construct(
        private FilesystemManager $filesystem,
    ) {}

    /**
     * The image is private, so it is read from the `local` disk and returned
     * inline with sniffing and shared caching switched off.
     */
    public function handle(Message $message): BinaryFileResponse
    {
        $disk = $this->filesystem->disk('local');

        abort_if($message->image_path === null || ! $disk->exists($message->image_path), 404);

        $response = response()->file($disk->path($message->image_path), [
            'Content-Type' => $message->image_mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
