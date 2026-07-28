<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\DisableAccount;
use App\Actions\Account\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DisableAccountRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('account/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'canDisableAccount' => $request->user()->can('disableAccount', $request->user()),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request->user(), [
            'name' => (string) $request->validated('name'),
            'email' => (string) $request->validated('email'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.message.updated')]);

        return to_route('account.profile.edit');
    }

    /**
     * Disable the user's account.
     */
    public function disable(DisableAccountRequest $request, DisableAccount $disableAccount): RedirectResponse
    {
        $user = $request->user();

        $disableAccount->handle($user);
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
