<?php

namespace App\Actions\Chat;

use App\Models\Chat\AuditLog;
use App\Models\Chat\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

final class RecordConversationAccess
{
    /**
     * Record that a Super Admin opened the contents of a conversation.
     *
     * Only access metadata is stored, never a copy of what was read, and the
     * record is permanent. Nothing here touches read markers, unread counts, or
     * the participants' notifications, so an audit is invisible to them.
     */
    public function handle(Conversation $conversation, User $viewer, Request $request): AuditLog
    {
        return AuditLog::query()->create([
            'conversation_id' => $conversation->id,
            'viewer_id' => $viewer->id,
            'viewed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }
}
