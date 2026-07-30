<?php

namespace App\Http\Controllers\MasterData;

use App\Actions\Users\CreateManagedUser;
use App\Actions\Users\UpdateUser;
use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\DefaultUserPasswordNotConfigured;
use App\Http\Controllers\Controller;
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
            'users' => $table->paginate(TableQuery::fromValidated($request->validated())),
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
            'roleOptions' => $this->roleOptions($catalog),
        ]);
    }

    /**
     * Provision a user from a name, an email, and one role.
     */
    public function store(StoreUserRequest $request, CreateManagedUser $createManagedUser): RedirectResponse
    {
        try {
            $createManagedUser->handle([
                'name' => (string) $request->validated('name'),
                'email' => (string) $request->validated('email'),
                'role' => (string) $request->validated('role'),
            ]);
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

        return Inertia::render('master-data/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'role' => $this->currentRole($user),
            ],
            'roleOptions' => $this->roleOptions($catalog),
            'statusOptions' => UserStatus::options(),
        ]);
    }

    /**
     * Apply the name, email, status, and role changes to an existing user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, [
            'name' => (string) $request->validated('name'),
            'email' => (string) $request->validated('email'),
            'status' => (string) $request->validated('status'),
            'role' => (string) $request->validated('role'),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('master_data.user.message.updated'),
        ]);

        return to_route('master-data.users.index');
    }

    /**
     * The roles user management is allowed to assign.
     *
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(AuthorizationCatalog $catalog): array
    {
        return array_map(
            fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            $catalog->assignableRoles(),
        );
    }

    /**
     * The single role the edit form should preselect.
     */
    private function currentRole(User $user): ?string
    {
        foreach (Role::cases() as $role) {
            if ($user->hasRole($role->value)) {
                return $role->value;
            }
        }

        return null;
    }
}
