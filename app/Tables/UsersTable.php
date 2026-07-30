<?php

namespace App\Tables;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The Master Data listing of application users.
 *
 * @extends AbstractTable<User>
 */
final class UsersTable extends AbstractTable
{
    /**
     * Client sort keys mapped to the columns they may order by.
     *
     * Public so the request validating the table query shares one source of
     * truth with the query that consumes it.
     */
    public const array SORTABLE = [
        'name' => 'name',
        'email' => 'email',
        'status' => 'status',
        'createdAt' => 'created_at',
    ];

    /**
     * The filter keys the table understands.
     *
     * Public for the same reason as `SORTABLE`: the request validating the
     * query and the controller building it share one source of truth.
     */
    public const array FILTERABLE = ['status', 'role'];

    public function __construct(private readonly User $actor) {}

    /**
     * @return Builder<User>
     */
    protected function query(): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'status', 'is_system', 'created_at'])
            ->with('roles:id,name');
    }

    /**
     * @return list<string>
     */
    protected function searchable(): array
    {
        return ['name', 'email'];
    }

    /**
     * @return non-empty-array<string, string>
     */
    protected function sortable(): array
    {
        return self::SORTABLE;
    }

    protected function defaultSort(): string
    {
        return 'createdAt';
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, list<string>>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (($statuses = $filters['status'] ?? []) !== []) {
            $query->whereIn('status', $statuses);
        }

        if (($roles = $filters['role'] ?? []) !== []) {
            $query->whereHas('roles', function (Builder $roleQuery) use ($roles): void {
                $roleQuery->whereIn('name', $roles);
            });
        }
    }

    /**
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function transform(Model $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'email' => $model->email,
            'role' => $this->role($model),
            'status' => [
                'value' => $model->status->value,
                'label' => $model->status->label(),
                'variant' => $model->status->variant(),
            ],
            /* The instant stays UTC ISO 8601; the browser owns the timezone. */
            'createdAt' => $model->created_at?->toISOString(),
            'canUpdate' => $this->actor->can('update', $model),
        ];
    }

    /**
     * Present a single role for the user, most privileged first.
     *
     * A legacy account may carry more than one role; picking from the catalog
     * order keeps the displayed role deterministic.
     *
     * @return array{value: string, label: string}|null
     */
    private function role(User $user): ?array
    {
        foreach (Role::cases() as $role) {
            if ($user->roles->contains('name', $role->value)) {
                return [
                    'value' => $role->value,
                    'label' => $role->label(),
                ];
            }
        }

        return null;
    }
}
