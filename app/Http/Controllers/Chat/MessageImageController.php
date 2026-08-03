<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RecordConversationAccess;
use App\Actions\Chat\ServeChatMessageImage;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Models\Chat\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageImageController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function show(
        Request $request,
        Message $message,
        ServeChatMessageImage $serveChatMessageImage,
    ): BinaryFileResponse {
        $message->load('conversation.participants.user');
        Gate::authorize('view', $message->conversation);

        return $serveChatMessageImage->handle($message);
    }

    public function audit(
        Request $request,
        Message $message,
        RecordConversationAccess $recordConversationAccess,
        ServeChatMessageImage $serveChatMessageImage,
    ): BinaryFileResponse {
        Gate::authorize('audit', $message->conversation);
        $recordConversationAccess->handle(
            $message->conversation,
            $this->authenticatedUser($request),
            $request,
        );

        return $serveChatMessageImage->handle($message);
    }
}
