<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\UpdatePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\TwoFactorAuthenticationRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $props = [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'mustChangePassword' => $request->user()->must_change_password,
        ];

        return Inertia::render('account/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request, UpdatePassword $updatePassword): RedirectResponse
    {
        $mustChangePassword = $request->user()->must_change_password;

        $updatePassword->handle($request->user(), $request->password);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.security.message.updated')]);

        return $mustChangePassword
            ? to_route('dashboard')
            : back();
    }
}
