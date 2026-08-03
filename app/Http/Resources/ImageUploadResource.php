<?php

namespace App\Http\Resources;

use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Http\Presenters\ChatPresenter;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * The only view of an upload the client is ever given.
 *
 * Deliberately narrow: the private staging path, the encrypted payload, and the
 * lock are all internal and never leave the server. What a client gets is the
 * state it polls, a safe error code it can translate, and — once the upload is
 * ready — the result it was waiting for.
 *
 * @mixin ImageUpload
 */
class ImageUploadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'target' => $this->target->value,
            /* Names the branding asset, so a page can match an upload to its field. */
            'target_key' => $this->target === ImageUploadTarget::Branding
                ? $this->target_key
                : null,
            'status' => $this->status->value,
            'error_code' => $this->error_code,
            'created_at' => $this->created_at?->toIso8601String(),
            /* Where this upload is polled and abandoned; both owner-only. */
            'poll_url' => route('media.image-uploads.show', $this->resource),
            'cancel_url' => route('media.image-uploads.destroy', $this->resource),
            'url' => $this->publicUrl(),
            'message' => $this->chatMessage(),
            'conversation' => $this->chatConversation($request),
        ];
    }

    /**
     * The published image's URL, once there is one to link to.
     *
     * Chat images stay private and are reached through their message instead.
     */
    private function publicUrl(): ?string
    {
        if ($this->status !== ImageUploadStatus::Ready || $this->result_path === null) {
            return null;
        }

        return match ($this->target) {
            ImageUploadTarget::Avatar, ImageUploadTarget::Branding => Storage::disk(
                $this->target->disk(),
            )->url($this->result_path),
            ImageUploadTarget::ChatImage => null,
        };
    }

    /**
     * The message a finished chat upload produced, ready to render.
     *
     * Nothing is loaded here: the caller decides which uploads are worth the
     * result graph, so a collection of uploads can never fan out into queries.
     *
     * @return array<string, mixed>|null
     */
    private function chatMessage(): ?array
    {
        $message = $this->loadedResultMessage();

        if (! $message instanceof Message) {
            return null;
        }

        return ChatPresenter::message($message);
    }

    /**
     * The conversation the finished message belongs to.
     *
     * A first message opens its conversation inside the job, so this is how the
     * client learns which one it ended up in.
     *
     * @return array<string, mixed>|null
     */
    private function chatConversation(Request $request): ?array
    {
        $viewer = $request->user();
        $message = $this->loadedResultMessage();

        if (! $viewer instanceof User || ! $message instanceof Message) {
            return null;
        }

        $conversation = $message->relationLoaded('conversation')
            ? $message->conversation
            : null;

        if (! $conversation instanceof Conversation) {
            return null;
        }

        $conversation->setRelation('latestMessage', $message);

        return ChatPresenter::conversation($conversation, $viewer);
    }

    /**
     * The produced chat message, but only when it is already in memory.
     *
     * An upload whose result was never preloaded — or whose message has since
     * been removed — presents as no result at all rather than a lazy query.
     */
    private function loadedResultMessage(): ?Message
    {
        if ($this->target !== ImageUploadTarget::ChatImage
            || $this->status !== ImageUploadStatus::Ready
            || ! $this->resource->relationLoaded('resultMessage')) {
            return null;
        }

        return $this->resource->resultMessage;
    }
}
