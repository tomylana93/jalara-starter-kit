<?php

namespace App\Http\Presenters;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Chat\AuditLog;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\Chat\Reaction;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds every chat payload that crosses the boundary to the client.
 *
 * The User model is never shared directly: only the fields below travel, so
 * email, phone, storage paths, and the exact account status stay on the server.
 * A peer whose account is no longer Active is described by `available` alone.
 */
final class ChatPresenter
{
    /**
     * @return array{id: string, name: string, avatar: string|null, role: string|null, available: bool}
     */
    public static function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'role' => self::roleLabel($user),
            'available' => $user->status === UserStatus::Active,
        ];
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{id: string, name: string, avatar: string|null, role: string|null, available: bool}>
     */
    public static function profiles(Collection $users): array
    {
        return array_values($users->map(self::profile(...))->all());
    }

    /**
     * @return array{id: string, conversation_id: string, sender_id: string, body: string|null, image: array{url: string}|null, reactions: list<array{id: string, user_id: string, emoji: string}>, created_at: string|null}
     */
    public static function message(Message $message, bool $audit = false): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'body' => $message->body,
            'image' => $message->image_path === null ? null : [
                'url' => route($audit ? 'chat.audit.messages.image' : 'chat.messages.image', $message),
            ],
            'reactions' => $message->relationLoaded('reactions')
                ? array_values($message->reactions->map(
                    fn (Reaction $reaction): array => self::reaction($reaction),
                )->all())
                : [],
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /** @return array{id: string, user_id: string, emoji: string}|null */
    public static function reaction(?Reaction $reaction): ?array
    {
        if (! $reaction instanceof Reaction) {
            return null;
        }

        return [
            'id' => $reaction->id,
            'user_id' => $reaction->user_id,
            'emoji' => $reaction->emoji,
        ];
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    public static function messages(Collection $messages): array
    {
        return array_values($messages->map(
            fn (Message $message): array => self::message($message),
        )->all());
    }

    /**
     * Summarize one conversation from the viewpoint of the given participant.
     *
     * `unread_count` counts what the viewer has not read, `peer_read_at` is the
     * other side's read receipt, and `last_message` carries only the preview the
     * inbox renders.
     *
     * @return array{
     *     id: string,
     *     participant: array{id: string, name: string, avatar: string|null, role: string|null, available: bool}|null,
     *     last_message: array{id: string, conversation_id: string, sender_id: string, body: string, created_at: string|null}|null,
     *     last_message_at: string|null,
     *     unread_count: int,
     *     last_read_at: string|null,
     *     peer_read_at: string|null,
     * }
     */
    public static function conversation(Conversation $conversation, User $viewer, int $unreadCount = 0): array
    {
        $participant = $conversation->participantFor($viewer);
        $counterpart = $conversation->counterpartFor($viewer);
        $lastMessage = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'participant' => $counterpart?->user instanceof User
                ? self::profile($counterpart->user)
                : null,
            'last_message' => $lastMessage instanceof Message ? self::message($lastMessage) : null,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => $unreadCount,
            'last_read_at' => $participant?->last_read_at?->toIso8601String(),
            'peer_read_at' => $counterpart?->last_read_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, Conversation>  $conversations
     * @param  array<string, int>  $unreadCounts  Conversation id to unread total.
     * @return list<array<string, mixed>>
     */
    public static function conversations(Collection $conversations, User $viewer, array $unreadCounts = []): array
    {
        return array_values(
            $conversations
                ->map(fn (Conversation $conversation): array => self::conversation(
                    $conversation,
                    $viewer,
                    $unreadCounts[$conversation->id] ?? 0,
                ))
                ->all(),
        );
    }

    /**
     * Both sides of a conversation, as the audit surface lists them.
     *
     * @return list<array{id: string, name: string, avatar: string|null, role: string|null, available: bool}>
     */
    public static function participants(Conversation $conversation): array
    {
        return array_values(
            $conversation->participants
                ->map(fn (Participant $participant): array => self::profile($participant->user))
                ->all(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     viewer: array{id: string, name: string},
     *     viewed_at: string|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     * }
     */
    public static function auditLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'viewer' => [
                'id' => $log->viewer_id,
                'name' => $log->viewer->name,
            ],
            'viewed_at' => $log->viewed_at->toIso8601String(),
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
        ];
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     * @return list<array<string, mixed>>
     */
    public static function auditLogs(Collection $logs): array
    {
        return array_values($logs->map(self::auditLog(...))->all());
    }

    /**
     * The localized label of the user's first role, when one is assigned.
     */
    private static function roleLabel(User $user): ?string
    {
        $name = $user->getRoleNames()->first();

        if (! is_string($name)) {
            return null;
        }

        return Role::tryFrom($name)?->label() ?? $name;
    }
}
