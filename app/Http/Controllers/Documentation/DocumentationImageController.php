<?php

namespace App\Http\Controllers\Documentation;

use App\Actions\Media\StageImageUpload;
use App\Enums\ImageUploadTarget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documentation\StoreDocumentationImageRequest;
use App\Http\Resources\ImageUploadResource;
use App\Jobs\Media\ProcessDocumentationImageUpload;
use Illuminate\Http\JsonResponse;

class DocumentationImageController extends Controller
{
    /**
     * Accept an editor image and hand it to the queue.
     *
     * The endpoint is deliberately independent of any document: an author
     * uploads while writing, before a new document has an id, and the image
     * only becomes part of a document when that document is saved.
     */
    public function __invoke(StoreDocumentationImageRequest $request, StageImageUpload $stageImageUpload): JsonResponse
    {
        $upload = $stageImageUpload->handle(
            $request->user(),
            $request->file('image'),
            ImageUploadTarget::DocumentationImage,
        );

        dispatch(new ProcessDocumentationImageUpload($upload));

        return new ImageUploadResource($upload)->response()->setStatusCode(202);
    }
}
