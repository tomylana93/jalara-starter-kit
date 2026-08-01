<?php

namespace App\Models\Chat;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Chat\ReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $message_id
 * @property string $user_id
 * @property string $emoji
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Message $message
 * @property-read User $user
 */
#[Fillable(['message_id', 'user_id', 'emoji'])]
#[Table(name: 'chat_message_reactions')]
class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory, HasUuids;

    public const array ALLOWED = ['👍', '❤️', '😂', '😮', '😢', '🙏', '👎', '🎉', '🔥', '👀', '🤔', '😡'];

    /** @return BelongsTo<Message, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
