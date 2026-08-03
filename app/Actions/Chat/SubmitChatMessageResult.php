<?php

namespace App\Actions\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\ImageUpload;
use LogicException;

/**
 * What one submission produced: a stored message, or an accepted upload.
 *
 * The two outcomes are genuinely different — a text message exists by the time
 * the caller sees this, while an image message is only promised — so the caller
 * has to ask which one it is holding before reading the rest.
 */
final readonly class SubmitChatMessageResult
{
    private function __construct(
        private ?Conversation $conversation,
        private ?Message $message,
        private ?ImageUpload $upload,
    ) {}

    /**
     * The message was stored; the conversation carries the graph its payload
     * needs.
     */
    public static function sent(Conversation $conversation, Message $message): self
    {
        return new self($conversation, $message, null);
    }

    /**
     * The image was staged; the queue creates the message once it is good.
     */
    public static function accepted(ImageUpload $upload): self
    {
        return new self(null, null, $upload);
    }

    /**
     * The staged upload, when the submission was only accepted.
     */
    public function acceptedUpload(): ?ImageUpload
    {
        return $this->upload;
    }

    public function conversation(): Conversation
    {
        return $this->conversation ?? throw new LogicException('This submission was accepted, not sent.');
    }

    public function message(): Message
    {
        return $this->message ?? throw new LogicException('This submission was accepted, not sent.');
    }
}
