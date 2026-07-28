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
        ];

        return Inertia::render('account/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($request->user(), $request->password);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
