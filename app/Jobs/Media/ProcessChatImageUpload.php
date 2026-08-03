<?php

namespace App\Jobs\Media;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Enums\UserStatus;
use App\Models\Chat\Conversation;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Gate;

/**
 * Publishes a processed chat image as a new message.
 *
 * Unlike avatar and branding, nothing exists yet to replace: the message — and,
 * for a pair's first message, the conversation itself — is created here, once
 * the image is known to be good. That ordering is deliberate. A failed upload
 * must not leave an empty conversation behind, which is exactly what would
 * happen if the conversation were opened when the request arrived.
 *
 * The message is broadcast and notified by `SendMessage`, so an image message
 * reaches the recipient through the same path a text message does.
 */
class ProcessChatImageUpload extends ProcessQueuedImageUpload
{
    protected function authorizePublication(ImageUpload $upload, User $owner): bool
    {
        $settings = app(ChatSettings::class);

        /* Either toggle being switched off while queued withdraws the upload. */
        if (! $settings->chatEnabled || ! $settings->imageUploadsEnabled) {
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

    /**
     * Keyed by the upload rather than the conversation, which may not exist yet.
     */
    protected function destinationDirectory(ImageUpload $upload, User $owner): string
    {
        return sprintf('chat/%s', $upload->getKey());
    }

    protected function publish(ImageUpload $upload, User $owner, string $path, string $mimeType): void
    {
        $conversation = $this->existingConversation($upload);

        if (! $conversation instanceof Conversation) {
            $recipient = $this->recipient($upload);

            /* Guarded by `authorizePublication`, which runs immediately before. */
            if (! $recipient instanceof User) {
                return;
            }

            $conversation = app(StartConversation::class)->handle($owner, $recipient);
        }

        $body = $upload->payload['body'] ?? null;

        $message = app(SendMessage::class)->handle(
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

        return User::query()->with('roles')->find($recipientId);
    }
}
