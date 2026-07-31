<?php

namespace App\Models\Chat;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Chat\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable direct message. Nothing in the application edits or deletes one.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string $sender_id
 * @property string $body
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Conversation $conversation
 * @property-read User $sender
 */
#[Fillable(['conversation_id', 'sender_id', 'body'])]
#[Table(name: 'chat_messages')]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUuids;

    /**
     * The longest body a single message may carry.
     */
    public const int MAX_LENGTH = 4000;

    /**
     * How many messages one window of a conversation carries.
     */
    public const int WINDOW = 30;

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
