<?php

namespace App\Http\Controllers\Chat;

use App\Concerns\ResolvesAuthenticatedUser;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ChatPresenter;
use App\Http\Requests\Chat\SearchRecipientRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RecipientController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * The longest answer the directory returns for one term.
     */
    private const int LIMIT = 20;

    /**
     * Search Active users by name.
     *
     * Only the name is searchable, and only the presenter's fields are
     * returned, so the directory never becomes a way to enumerate email
     * addresses or account states. The viewer is excluded: a direct message to
     * oneself is not a conversation.
     */
    public function index(SearchRecipientRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $search = (string) $request->validated('search');

        $recipients = User::query()
            ->whereKeyNot($user->id)
            ->where('status', UserStatus::Active)
            ->whereLike('name', '%'.$search.'%')
            ->with('roles')
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'data' => ChatPresenter::profiles($recipients),
        ]);
    }
}
