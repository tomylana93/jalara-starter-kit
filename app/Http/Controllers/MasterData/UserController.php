<?php

namespace App\Http\Controllers\MasterData;

use App\Actions\Users\CreateManagedUser;
use App\Actions\Users\UpdateUser;
use App\Authorization\AuthorizationCatalog;
use App\Enums\UserStatus;
use App\Exceptions\DefaultUserPasswordNotConfigured;
use App\Http\Controllers\Controller;
use App\Http\Presenters\UserManagementPresenter;
use App\Http\Requests\MasterData\IndexUserRequest;
use App\Http\Requests\MasterData\StoreUserRequest;
use App\Http\Requests\MasterData\UpdateUserRequest;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Tables\TableQuery;
use App\Tables\UsersTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the server-side user table.
     */
    public function index(IndexUserRequest $request, GeneralSettings $generalSettings): Response
    {
        $table = new UsersTable($request->user());

        return Inertia::render('master-data/users/Index', [
            'users' => $table->paginate(
                TableQuery::fromValidated($request->validated(), UsersTable::FILTERABLE),
            ),
            'filterOptions' => UserManagementPresenter::filterOptions(),
            'canCreate' => Gate::allows('create', User::class),
            'dateFormat' => $generalSettings->dateFormat->value,
        ]);
    }

    /**
     * Show the form that provisions a user.
     */
    public function create(AuthorizationCatalog $catalog): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('master-data/users/Create', [
            'roleOptions' => UserManagementPresenter::roleOptions($catalog),
        ]);
    }

    /**
     * Provision a user from a name, an email, and one role.
     */
    public function store(StoreUserRequest $request, CreateManagedUser $createManagedUser): RedirectResponse
    {
        try {
            $createManagedUser->handle($request->toData());
        } catch (DefaultUserPasswordNotConfigured $defaultUserPasswordNotConfigured) {
            /* Provisioning needs a default password configured in settings. */
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $defaultUserPasswordNotConfigured->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('master_data.user.message.created'),
        ]);

        return to_route('master-data.users.index');
    }

    /**
     * Show the form that edits an existing user.
     */
    public function edit(User $user, AuthorizationCatalog $catalog): Response
    {
        Gate::authorize('update', $user);

        $user->loadMissing('roles');

        return Inertia::render('master-data/users/Edit', [
            'user' => UserManagementPresenter::editUser($user),
            'roleOptions' => UserManagementPresenter::roleOptions($catalog),
            'statusOptions' => UserStatus::options(),
        ]);
    }

    /**
     * Apply the name, email, status, and role changes to an existing user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->toData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('master_data.user.message.updated'),
        ]);

        return to_route('master-data.users.index');
    }
}
