<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Actions\Media\StageImageUpload;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Enums\ImageUploadTarget;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Resources\ImageUploadResource;
use App\Jobs\Media\ProcessChatImageUpload;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class MessageController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Send one message, opening the conversation when it is the pair's first.
     *
     * A text message is stored immediately and answered with the message
     * itself. A message carrying an image cannot be: the image has to be
     * processed first, so the request is only *accepted* and the message is
     * created by the queue once the image is good.
     *
     * Either way nothing is stored until the body validates, and — crucially —
     * a first message with an image does not open its conversation here. Doing
     * so would leave an empty conversation behind every time processing failed.
     */
    public function store(
        StoreMessageRequest $request,
        StartConversation $startConversation,
        SendMessage $sendMessage,
        StageImageUpload $stageImageUpload,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $image = $request->file('image');

        if ($image instanceof UploadedFile) {
            return $this->acceptImageMessage($request, $user, $image, $stageImageUpload);
        }

        $conversationId = $request->validated('conversation_id');
        $isFirstMessage = ! is_string($conversationId);
        $conversation = $this->resolveConversation($request, $user, $startConversation);

        Gate::authorize('send', $conversation);

        try {
            $body = $request->validated('body');
            $message = $sendMessage->handle($conversation, $user, is_string($body) ? $body : null);
        } catch (Throwable $throwable) {
            if ($isFirstMessage && $conversation->messages()->doesntExist()) {
                $conversation->delete();
            }

            throw $throwable;
        }

        $conversation->load('participants.user')->setRelation('latestMessage', $message);

        return response()->json([
            'conversation' => ChatPresenter::conversation($conversation, $user),
            'message' => ChatPresenter::message($message),
        ], 201);
    }

    /**
     * Take an image-bearing message into the queue.
     *
     * Authorization is settled here, against the conversation or recipient the
     * client named, and settled again in the job before the message is created.
     */
    private function acceptImageMessage(
        StoreMessageRequest $request,
        User $user,
        UploadedFile $image,
        StageImageUpload $stageImageUpload,
    ): JsonResponse {
        $conversationId = $request->validated('conversation_id');
        $body = $request->validated('body');
        $payload = ['body' => is_string($body) ? $body : null];

        if (is_string($conversationId)) {
            $conversation = Conversation::query()->with('participants.user')->findOrFail($conversationId);

            /*
             * Resolved before the send policy so a stranger's identifier answers
             * 403 rather than revealing anything about the conversation.
             */
            Gate::authorize('view', $conversation);
            Gate::authorize('send', $conversation);

            $payload['conversation_id'] = $conversation->id;
        } else {
            $payload['recipient_id'] = $this->availableRecipient($request, $user)->id;
        }

        $upload = $stageImageUpload->handle(
            $user,
            $image,
            ImageUploadTarget::ChatImage,
            payload: $payload,
        );

        dispatch(new ProcessChatImageUpload($upload));

        return new ImageUploadResource($upload)->response()->setStatusCode(202);
    }

    /**
     * Resolve the conversation the message belongs to.
     */
    private function resolveConversation(
        StoreMessageRequest $request,
        User $user,
        StartConversation $startConversation,
    ): Conversation {
        $conversationId = $request->validated('conversation_id');

        if (is_string($conversationId)) {
            $conversation = Conversation::query()->with('participants.user')->findOrFail($conversationId);

            /*
             * Resolved before the send policy so a stranger's identifier answers
             * 403 rather than revealing anything about the conversation.
             */
            Gate::authorize('view', $conversation);

            return $conversation;
        }

        return $startConversation->handle($user, $this->availableRecipient($request, $user));
    }

    /**
     * The user a first message may be opened with.
     */
    private function availableRecipient(StoreMessageRequest $request, User $user): User
    {
        $recipient = User::query()->with('roles')->findOrFail((string) $request->validated('recipient_id'));

        if ($recipient->is($user) || $recipient->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'recipient_id' => __('chat.message.recipient_unavailable'),
            ]);
        }

        return $recipient;
    }
}
