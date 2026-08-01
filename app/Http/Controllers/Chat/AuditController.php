<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RecordConversationAccess;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\IndexAuditRequest;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Super Admin's read-only view of every direct message.
 *
 * This surface never sends, edits, or deletes anything, and it never touches a
 * participant's read marker, unread count, or notifications, so an audit stays
 * invisible to the two people in the conversation. Opening the contents of a
 * conversation writes a permanent access record.
 */
class AuditController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Page names for the two scroll containers on the transcript screen, kept
     * distinct so neither fights over a single `page` query parameter.
     */
    public const string MESSAGES_PAGE = 'messages';

    public const string LOGS_PAGE = 'logs';

    /**
     * How many messages, and how many access records, one page carries.
     */
    private const int MESSAGES_PER_PAGE = 50;

    private const int LOGS_PER_PAGE = 20;

    /**
     * List every conversation in the application, newest activity first.
     *
     * The optional term matches participant names. Message bodies are
     * deliberately not searchable from here.
     */
    public function index(IndexAuditRequest $request): Response
    {
        Gate::authorize('audit', Conversation::class);

        $search = $request->validated('search');
        $search = is_string($search) ? trim($search) : '';

        $conversations = Conversation::query()
            ->with(['participants.user', 'latestMessage.reactions'])
            ->withCount('messages')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'participants.user',
                fn (Builder $participants) => $participants->whereLike('name', '%'.$search.'%'),
            ))
            ->latest('last_message_at')
            ->orderByDesc('id')
            ->paginate(perPage: Conversation::PER_PAGE, pageName: 'conversations');

        $conversations->through(fn (Conversation $conversation): array => [
            'id' => $conversation->id,
            'participants' => $this->participants($conversation),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'message_count' => (int) ($conversation->messages_count ?? 0),
        ]);

        return Inertia::render('chat/audit/Index', [
            'conversations' => Inertia::scroll($conversations),
            'search' => $search === '' ? null : $search,
        ]);
    }

    /**
     * Show one conversation's contents and record the access.
     *
     * Both the transcript and its access log are scroll props, so the whole
     * history is reachable rather than a fixed first window.
     */
    public function show(
        Request $request,
        Conversation $conversation,
        RecordConversationAccess $recordConversationAccess,
    ): Response {
        Gate::authorize('audit', Conversation::class);

        $viewer = $this->authenticatedUser($request);

        $conversation->load('participants.user');

        $recordConversationAccess->handle($conversation, $viewer, $request);

        $messages = $conversation->messages()
            ->with('reactions')
            ->oldest()
            ->orderBy('id')
            ->paginate(perPage: self::MESSAGES_PER_PAGE, pageName: self::MESSAGES_PAGE);

        $messages->through(fn (Message $message): array => ChatPresenter::message($message, true));

        $logs = $conversation->auditLogs()
            ->with('viewer')
            ->latest('viewed_at')
            ->orderByDesc('id')
            ->paginate(perPage: self::LOGS_PER_PAGE, pageName: self::LOGS_PAGE);

        $logs->through(ChatPresenter::auditLog(...));

        return Inertia::render('chat/audit/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'participants' => $this->participants($conversation),
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'message_count' => $messages->total(),
            ],
            'messages' => Inertia::scroll($messages),
            'auditLogs' => Inertia::scroll($logs),
        ]);
    }

    /**
     * @return list<array{id: string, name: string, avatar: string|null, role: string|null, available: bool}>
     */
    private function participants(Conversation $conversation): array
    {
        return array_values(
            $conversation->participants
                ->map(fn (Participant $participant): array => ChatPresenter::profile($participant->user))
                ->all(),
        );
    }
}
