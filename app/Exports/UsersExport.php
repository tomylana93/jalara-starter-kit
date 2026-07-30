<?php

namespace App\Exports;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelWriter;

/**
 * A spreadsheet of the users a request selected.
 *
 * The export mirrors what the table already shows and nothing more: no
 * password, token, or other credential material ever reaches the file.
 */
final class UsersExport
{
    /**
     * Write the selected users to a spreadsheet and return its path.
     *
     * @param  list<string>  $ids
     */
    public function write(array $ids): string
    {
        $path = tempnam(sys_get_temp_dir(), 'users-export').'.xlsx';

        $writer = SimpleExcelWriter::create($path)->addHeader($this->heading());

        foreach ($this->users($ids) as $user) {
            $writer->addRow($this->row($user));
        }

        $writer->close();

        return $path;
    }

    /**
     * The column titles, localized like the table they came from.
     *
     * @return list<string>
     */
    private function heading(): array
    {
        return [
            __('master_data.user.label.id'),
            __('master_data.user.label.name'),
            __('master_data.user.label.email'),
            __('master_data.user.label.role'),
            __('master_data.user.label.status'),
            __('master_data.user.label.created_at'),
        ];
    }

    /**
     * The selected users, restored to the order they were selected in.
     *
     * @param  list<string>  $ids
     * @return Collection<int, User>
     */
    private function users(array $ids): Collection
    {
        $position = array_flip($ids);

        return User::query()
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->with('roles:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (User $user): int => $position[$user->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @return list<string>
     */
    private function row(User $user): array
    {
        return [
            (string) $user->id,
            $user->name,
            $user->email,
            $this->role($user) ?? __('master_data.user.role_missing'),
            $user->status->label(),
            /* The instant stays UTC ISO 8601, exactly as the table sends it. */
            $user->created_at?->toISOString() ?? '',
        ];
    }

    /**
     * Present a single role, most privileged first, as the table does.
     */
    private function role(User $user): ?string
    {
        foreach (Role::cases() as $role) {
            if ($user->roles->contains('name', $role->value)) {
                return $role->label();
            }
        }

        return null;
    }
}
