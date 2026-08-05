<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\CreateApiToken;
use App\Actions\Account\RevokeApiToken;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ApiTokenPresenter;
use App\Http\Requests\Account\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Show the user's personal access tokens.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('account/ApiTokens', [
            'tokens' => ApiTokenPresenter::forUser($request->user()),
        ]);
    }

    /**
     * Issue a personal access token.
     *
     * The plain text is flashed rather than returned as a prop, because a prop
     * would survive every partial reload of this page and keep re-displaying a
     * secret the user has already been shown once.
     */
    public function store(StoreApiTokenRequest $request, CreateApiToken $createApiToken): RedirectResponse
    {
        $token = $createApiToken->handle($request->user(), $request->string('name')->toString());

        Inertia::flash('createdApiToken', [
            'name' => $token->accessToken->name,
            'plainText' => $token->plainTextToken,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.api_token.message.created')]);

        return back();
    }

    /**
     * Revoke one of the user's personal access tokens.
     */
    public function destroy(Request $request, RevokeApiToken $revokeApiToken, string $token): RedirectResponse
    {
        $revokeApiToken->handle($request->user(), $token);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.api_token.message.revoked')]);

        return back();
    }
}
