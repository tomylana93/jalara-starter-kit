<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\DisableAccount;
use App\Actions\Account\RemoveAvatar;
use App\Actions\Account\UpdateProfile;
use App\Actions\Media\StageImageUpload;
use App\Enums\ImageUploadTarget;
use App\Exceptions\Media\ActiveImageUploadExists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DisableAccountRequest;
use App\Http\Requests\Account\UpdateAvatarRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Resources\ImageUploadResource;
use App\Jobs\Media\ProcessAvatarImageUpload;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
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
        $updateProfile->handle($request->user(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.message.updated')]);

        return to_route('account.profile.edit');
    }

    /**
     * Accept a new avatar and hand it to the queue.
     *
     * The response is deliberately not a redirect: the image is not the user's
     * avatar yet, only accepted for processing, and the client follows it from
     * the polling URL in the body.
     */
    public function storeAvatar(UpdateAvatarRequest $request, StageImageUpload $stageImageUpload): JsonResponse
    {
        try {
            $upload = $stageImageUpload->handle(
                $request->user(),
                $request->file('image'),
                ImageUploadTarget::Avatar,
            );
        } catch (ActiveImageUploadExists $activeImageUploadExists) {
            /* The client is told which upload is in the way so it can resume it. */
            return new ImageUploadResource($activeImageUploadExists->existing)->response()->setStatusCode(409);
        }

        dispatch(new ProcessAvatarImageUpload($upload));

        return new ImageUploadResource($upload)->response()->setStatusCode(202);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function destroyAvatar(Request $request, RemoveAvatar $removeAvatar): RedirectResponse
    {
        $removeAvatar->handle($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.message.avatar_removed')]);

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
