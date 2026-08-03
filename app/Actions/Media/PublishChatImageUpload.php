<?php

namespace App\Actions\Media;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Enums\UserStatus;
use App\Models\Chat\Conversation;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Gate;

final readonly class PublishChatImageUpload implements ImageUploadPublication
{
    public function __construct(
        private ChatSettings $chatSettings,
        private StartConversation $startConversation,
        private SendMessage $sendMessage,
    ) {}

    public function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        /* Either toggle being switched off while queued withdraws the upload. */
        if (! $this->chatSettings->chatEnabled || ! $this->chatSettings->imageUploadsEnabled) {
            return false;
        }

        if ($owner->status !== UserStatus::Active) {
            return false;
        }

        $conversation = $this->existingConversation($upload);

        if ($conversation instanceof Conversation) {
            return Gate::forUser($owner)->allows('view', $conversation)
                && Gate::forUser($owner)->allows('send', $conversation);
        }

        $recipient = $this->recipient($upload);

        return $recipient instanceof User
            && ! $recipient->is($owner)
            && $recipient->status === UserStatus::Active;
    }

    public function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return sprintf('chat/%s', $upload->getKey());
    }

    public function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        $conversation = $this->existingConversation($upload);

        if (! $conversation instanceof Conversation) {
            $recipient = $this->recipient($upload);

            /* Guarded by `authorizePublication`, which runs immediately before. */
            if (! $recipient instanceof User) {
                return;
            }

            $conversation = $this->startConversation->handle($owner, $recipient);
        }

        $body = $upload->payload['body'] ?? null;

        $message = $this->sendMessage->handle(
            $conversation,
            $owner,
            is_string($body) ? $body : null,
            $path,
            $mimeType,
        );

        /*
         * Recorded on the instance the completing action is about to save, so a
         * client polling this upload can be handed the message it produced
         * rather than having to hunt for it.
         */
        $upload->target_key = $message->getKey();
    }

    /**
     * The conversation the message belongs to, when the client named one.
     */
    private function existingConversation(ImageUpload $upload): ?Conversation
    {
        $conversationId = $upload->payload['conversation_id'] ?? null;

        if (! is_string($conversationId)) {
            return null;
        }

        return Conversation::query()->with('participants.user')->find($conversationId);
    }

    /**
     * The user a first message opens the conversation with.
     */
    private function recipient(ImageUpload $upload): ?User
    {
        $recipientId = $upload->payload['recipient_id'] ?? null;

        if (! is_string($recipientId)) {
            return null;
        }

        return User::query()->find($recipientId);
    }
}
