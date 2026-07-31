<?php

namespace App\Models\Chat;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Chat\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection as SupportCollection;

/**
 * A one-to-one direct message between exactly two users.
 *
 * @property string $id
 * @property string $participant_key
 * @property CarbonInterface|null $last_message_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read int|null $messages_count
 * @property-read Message|null $latestMessage
 * @property-read Collection<int, Participant> $participants
 * @property-read Collection<int, Message> $messages
 */
#[Fillable(['participant_key', 'last_message_at'])]
#[Table(name: 'chat_conversations')]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUuids;

    /**
     * How many conversations one page of the inbox carries.
     */
    public const int PER_PAGE = 20;

    /**
     * The inbox query for one user: their own conversations, newest activity
     * first, with everything the summary payload needs already loaded.
     *
     * The id breaks ties, because two conversations can share a
     * `last_message_at` second and an ambiguous order would let a row repeat or
     * vanish between pages.
     *
     * @return Builder<static>
     */
    public static function inboxFor(User $user): Builder
    {
        return static::query()
            ->whereHas('participants', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(['participants.user', 'latestMessage'])
            ->latest('last_message_at')
            ->orderByDesc('id');
    }

    /**
     * Build the canonical key for a pair of participants.
     *
     * Sorting makes the key independent of who opens the conversation, which is
     * what lets the unique index reject a duplicate direct message.
     */
    public static function participantKeyFor(string $firstUserId, string $secondUserId): string
    {
        $ids = [$firstUserId, $secondUserId];
        sort($ids);

        return implode(':', $ids);
    }

    /**
     * Count the messages the given user has not read yet, for many
     * conversations at once.
     *
     * Every conversation carries its own read marker, so the counts are
     * gathered in a single grouped query rather than one query per row.
     *
     * @param  SupportCollection<int, Conversation>  $conversations  With `participants` loaded.
     * @return array<string, int>
     */
    public static function unreadCountsFor(SupportCollection $conversations, User $user): array
    {
        if ($conversations->isEmpty()) {
            return [];
        }

        $counts = Message::query()
            ->where('sender_id', '!=', $user->id)
            ->where(function ($query) use ($conversations, $user): void {
                foreach ($conversations as $conversation) {
                    $lastReadAt = $conversation->participantFor($user)?->last_read_at;

                    $query->orWhere(function ($scoped) use ($conversation, $lastReadAt): void {
                        $scoped->where('conversation_id', $conversation->id);

                        if ($lastReadAt !== null) {
                            $scoped->where('created_at', '>', $lastReadAt);
                        }
                    });
                }
            })
            ->selectRaw('conversation_id, count(*) as aggregate')
            ->groupBy('conversation_id')
            ->pluck('aggregate', 'conversation_id');

        /** @var array<string, int> $result */
        $result = $counts->map(fn (mixed $value): int => (int) $value)->all();

        return $result;
    }

    /**
     * Count everything the user has not read across all their conversations.
     *
     * Answered by one aggregate query, because the navigation badge runs on
     * every authenticated response.
     */
    public static function unreadMessageCountFor(User $user): int
    {
        return Message::query()
            ->join('chat_participants', function (JoinClause $join) use ($user): void {
                $join->on('chat_participants.conversation_id', '=', 'chat_messages.conversation_id')
                    ->where('chat_participants.user_id', '=', $user->id);
            })
            ->where('chat_messages.sender_id', '!=', $user->id)
            ->where(function ($query): void {
                $query->whereNull('chat_participants.last_read_at')
                    ->orWhereColumn('chat_messages.created_at', '>', 'chat_participants.last_read_at');
            })
            ->count();
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'conversation_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * The newest message, used for the inbox preview.
     *
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'conversation_id');
    }

    /**
     * Get the participant row belonging to the given user, when there is one.
     */
    public function participantFor(User $user): ?Participant
    {
        return $this->participants->first(fn (Participant $participant): bool => $participant->user_id === $user->id);
    }

    /**
     * Get the other side of the direct message.
     */
    public function counterpartFor(User $user): ?Participant
    {
        return $this->participants->first(fn (Participant $participant): bool => $participant->user_id !== $user->id);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }
}
