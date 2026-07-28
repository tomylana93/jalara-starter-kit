<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\DeleteAccount;
use App\Actions\Account\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DeleteAccountRequest;
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
     * Delete the user's account.
     */
    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $deleteAccount->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
