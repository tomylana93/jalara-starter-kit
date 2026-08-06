<?php

namespace App\Actions\Chat;

use App\Actions\Media\StageImageUpload;
use App\Data\Chat\SubmitChatMessageResult;
use App\Enums\ImageUploadTarget;
use App\Enums\UserStatus;
use App\Jobs\Media\ProcessChatImageUpload;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Sends one message, opening the conversation when it is the pair's first.
 *
 * A text message is stored immediately and answered with the message itself. A
 * message carrying an image cannot be: the image has to be processed first, so
 * the submission is only *accepted* and the message is created by the queue
 * once the image is good.
 *
 * Either way — and crucially — a first message with an image does not open its
 * conversation here. Doing so would leave an empty conversation behind every
 * time processing failed.
 */
final readonly class SubmitChatMessage
{
    public function __construct(
        private StartConversation $startConversation,
        private SendMessage $sendMessage,
        private StageImageUpload $stageImageUpload,
    ) {}

    public function handle(
        User $sender,
        ?string $conversationId,
        ?string $recipientId,
        ?string $body,
        ?UploadedFile $image = null,
    ): SubmitChatMessageResult {
        if ($image instanceof UploadedFile) {
            return $this->accept($sender, $conversationId, $recipientId, $body, $image);
        }

        $isFirstMessage = ! is_string($conversationId);
        $conversation = $this->resolveConversation($sender, $conversationId, $recipientId);

        Gate::forUser($sender)->authorize('send', $conversation);

        try {
            $message = $this->sendMessage->handle($conversation, $sender, $body);
        } catch (Throwable $throwable) {
            if ($isFirstMessage && $conversation->messages()->doesntExist()) {
                $conversation->delete();
            }

            throw $throwable;
        }

        /* Roles travel with the participants: the payload renders their labels. */
        $conversation->loadMissing('participants.user.roles')->setRelation('latestMessage', $message);

        return SubmitChatMessageResult::sent($conversation, $message);
    }

    /**
     * Take an image-bearing message into the queue.
     *
     * Authorization is settled here, against the conversation or recipient the
     * client named, and settled again in the job before the message is created.
     */
    private function accept(
        User $sender,
        ?string $conversationId,
        ?string $recipientId,
        ?string $body,
        UploadedFile $image,
    ): SubmitChatMessageResult {
        $payload = ['body' => $body];

        if (is_string($conversationId)) {
            $conversation = $this->existingConversation($sender, $conversationId);

            Gate::forUser($sender)->authorize('send', $conversation);

            $payload['conversation_id'] = $conversation->id;
        } else {
            $payload['recipient_id'] = $this->availableRecipient($sender, $recipientId)->id;
        }

        $upload = $this->stageImageUpload->handle(
            $sender,
            $image,
            ImageUploadTarget::ChatImage,
            payload: $payload,
        );

        dispatch(new ProcessChatImageUpload($upload));

        return SubmitChatMessageResult::accepted($upload);
    }

    /**
     * Resolve the conversation the message belongs to.
     */
    private function resolveConversation(User $sender, ?string $conversationId, ?string $recipientId): Conversation
    {
        if (is_string($conversationId)) {
            return $this->existingConversation($sender, $conversationId);
        }

        return $this->startConversation->handle($sender, $this->availableRecipient($sender, $recipientId));
    }

    /**
     * Read back a conversation the sender is allowed to know about.
     *
     * Only the participants the policy reads are loaded here; presentation-only
     * roles are added once authorization has passed.
     */
    private function existingConversation(User $sender, string $conversationId): Conversation
    {
        $conversation = Conversation::query()->with('participants.user')->findOrFail($conversationId);

        /*
         * Resolved before the send policy so a stranger's identifier answers
         * 403 rather than revealing anything about the conversation.
         */
        Gate::forUser($sender)->authorize('view', $conversation);

        return $conversation;
    }

    /**
     * The user a first message may be opened with.
     */
    private function availableRecipient(User $sender, ?string $recipientId): User
    {
        $recipient = User::query()->findOrFail((string) $recipientId);

        if ($recipient->is($sender) || $recipient->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'recipient_id' => __('chat.message.recipient_unavailable'),
            ]);
        }

        return $recipient;
    }
}
