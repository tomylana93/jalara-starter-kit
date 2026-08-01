<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RecordConversationAccess;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Models\Chat\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageImageController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function show(Request $request, Message $message): BinaryFileResponse
    {
        $message->load('conversation.participants.user');
        Gate::authorize('view', $message->conversation);

        return $this->imageResponse($message);
    }

    public function audit(
        Request $request,
        Message $message,
        RecordConversationAccess $recordConversationAccess,
    ): BinaryFileResponse {
        Gate::authorize('audit', $message->conversation);
        $recordConversationAccess->handle(
            $message->conversation,
            $this->authenticatedUser($request),
            $request,
        );

        return $this->imageResponse($message);
    }

    private function imageResponse(Message $message): BinaryFileResponse
    {
        abort_if($message->image_path === null || ! Storage::disk('local')->exists($message->image_path), 404);

        $response = response()->file(Storage::disk('local')->path($message->image_path), [
            'Content-Type' => $message->image_mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
