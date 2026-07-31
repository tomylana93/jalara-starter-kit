<?php

namespace App\Models\Chat;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Chat\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A permanent record that a Super Admin opened the contents of a conversation.
 *
 * Only access metadata is stored: no copy of any message body ever lands here.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string $viewer_id
 * @property CarbonInterface $viewed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Conversation $conversation
 * @property-read User $viewer
 */
#[Fillable(['conversation_id', 'viewer_id', 'viewed_at', 'ip_address', 'user_agent'])]
#[Table(name: 'chat_audit_logs')]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUuids;

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
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }
}
