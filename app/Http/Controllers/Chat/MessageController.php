<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SubmitChatMessage;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Resources\ImageUploadResource;
use App\Models\ImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class MessageController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Send one message, opening the conversation when it is the pair's first.
     *
     * A text message is stored immediately and answered with the message
     * itself; a message carrying an image is only accepted, because the queue
     * has to process the image before the message can exist.
     */
    public function store(StoreMessageRequest $request, SubmitChatMessage $submitChatMessage): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $image = $request->file('image');
        $conversationId = $request->validated('conversation_id');
        $recipientId = $request->validated('recipient_id');
        $body = $request->validated('body');

        $result = $submitChatMessage->handle(
            $user,
            is_string($conversationId) ? $conversationId : null,
            is_string($recipientId) ? $recipientId : null,
            is_string($body) ? $body : null,
            $image instanceof UploadedFile ? $image : null,
        );

        $upload = $result->acceptedUpload();

        if ($upload instanceof ImageUpload) {
            return new ImageUploadResource($upload)->response()->setStatusCode(202);
        }

        return response()->json([
            'conversation' => ChatPresenter::conversation($result->conversation(), $user),
            'message' => ChatPresenter::message($result->message()),
        ], 201);
    }
}
