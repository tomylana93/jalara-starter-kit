<?php

namespace App\Http\Controllers\Media;

use App\Actions\Media\CancelImageUpload;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImageUploadResource;
use App\Models\ImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * The status surface a client polls while its image is in the queue.
 *
 * Nothing here starts work; uploads are opened by the feature that owns the
 * target. This only reports on them and lets their owner change their mind.
 */
class ImageUploadController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Everything `ImageUploadResource` needs to render a finished chat upload.
     *
     * The resource itself queries nothing, so the controller is where the
     * result graph is selected — and only once authorization has passed.
     *
     * @var array<int, string>
     */
    private const array CHAT_RESULT_GRAPH = [
        'resultMessage.reactions',
        'resultMessage.conversation.participants.user.roles',
    ];

    /**
     * Every upload the caller still has in flight.
     *
     * This is what lets a page reload pick up where it left off rather than
     * losing track of an image that is still being processed.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $uploads = ImageUpload::query()
            ->where('user_id', $this->authenticatedUser($request)->getKey())
            ->active()
            ->latest()
            ->get();

        return ImageUploadResource::collection($uploads);
    }

    /**
     * Poll one upload.
     */
    public function show(Request $request, ImageUpload $imageUpload): ImageUploadResource
    {
        Gate::authorize('view', $imageUpload);

        /* Only an owner may reach the result graph the resource renders. */
        if ($imageUpload->target === ImageUploadTarget::ChatImage
            && $imageUpload->status === ImageUploadStatus::Ready) {
            $imageUpload->load(self::CHAT_RESULT_GRAPH);
        }

        return new ImageUploadResource($imageUpload);
    }

    /**
     * Ask for an upload to be abandoned.
     *
     * Best effort by nature: a worker may already be past the point of no
     * return, in which case the upload finishes and the response says so.
     */
    public function destroy(
        Request $request,
        ImageUpload $imageUpload,
        CancelImageUpload $cancelImageUpload,
    ): JsonResponse {
        Gate::authorize('cancel', $imageUpload);

        $cancelImageUpload->handle($imageUpload);

        $imageUpload->refresh();

        /* A cancellation that lost the race still answers with the result. */
        if ($imageUpload->target === ImageUploadTarget::ChatImage
            && $imageUpload->status === ImageUploadStatus::Ready) {
            $imageUpload->load(self::CHAT_RESULT_GRAPH);
        }

        return new ImageUploadResource($imageUpload)
            ->response()
            ->setStatusCode(200);
    }
}
