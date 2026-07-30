import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { DataTableColumnHeader } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { formatBrowserDateTime } from '@/lib/dateTime';
import UserRowActions from './components/UserRowActions.vue';

export type UserRoleCell = {
    value: string;
    label: string;
};

export type UserStatusCell = {
    value: string;
    label: string;
    variant: string;
};

export type UserRow = {
    id: string;
    name: string;
    email: string;
    role: UserRoleCell | null;
    status: UserStatusCell;
    createdAt: string | null;
    canUpdate: boolean;
};

type Translator = (key: string) => string;

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

const badgeVariants: BadgeVariant[] = [
    'default',
    'secondary',
    'destructive',
    'outline',
];

const toBadgeVariant = (variant: string): BadgeVariant =>
    badgeVariants.find((candidate) => candidate === variant) ?? 'secondary';

/**
 * The Master Data user table columns.
 *
 * The translator is injected because column definitions are built outside a
 * component setup scope.
 */
export const createUserColumns = (
    t: Translator,
    dateFormat: string,
    locale?: string,
): ColumnDef<UserRow>[] => [
    {
        id: 'select',
        enableSorting: false,
        enableHiding: false,
        header: ({ table }) =>
            h(Checkbox, {
                modelValue:
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(!!value),
                'aria-label': t('common.table.select.all'),
                'data-test': 'table-select-all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    row.toggleSelected(!!value),
                'aria-label': t('common.table.select.row'),
                'data-test': `table-select-row-${row.id}`,
            }),
    },
    {
        accessorKey: 'name',
        meta: { label: t('master_data.user.label.name') },
        header: ({ column }) =>
            h(DataTableColumnHeader, {
                column,
                title: t('master_data.user.label.name'),
            }),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.name),
    },
    {
        accessorKey: 'email',
        meta: { label: t('master_data.user.label.email') },
        header: ({ column }) =>
            h(DataTableColumnHeader, {
                column,
                title: t('master_data.user.label.email'),
            }),
        cell: ({ row }) =>
            h('span', { class: 'text-muted-foreground' }, row.original.email),
    },
    {
        accessorKey: 'role',
        enableSorting: false,
        meta: { label: t('master_data.user.label.role') },
        header: () => t('master_data.user.label.role'),
        cell: ({ row }) =>
            h(
                'span',
                row.original.role?.label ?? t('master_data.user.role_missing'),
            ),
    },
    {
        accessorKey: 'status',
        meta: { label: t('master_data.user.label.status') },
        header: ({ column }) =>
            h(DataTableColumnHeader, {
                column,
                title: t('master_data.user.label.status'),
            }),
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: toBadgeVariant(row.original.status.variant),
                    'data-test': `user-status-${row.original.id}`,
                },
                () => row.original.status.label,
            ),
    },
    {
        accessorKey: 'createdAt',
        meta: { label: t('master_data.user.label.created_at') },
        header: ({ column }) =>
            h(DataTableColumnHeader, {
                column,
                title: t('master_data.user.label.created_at'),
            }),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground whitespace-nowrap' },
                formatBrowserDateTime(
                    row.original.createdAt,
                    dateFormat,
                    locale,
                ),
            ),
    },
    {
        id: 'actions',
        enableSorting: false,
        enableHiding: false,
        header: () =>
            h(
                'span',
                { class: 'sr-only' },
                t('master_data.user.label.actions'),
            ),
        cell: ({ row }) =>
            h(UserRowActions, {
                id: row.original.id,
                canUpdate: row.original.canUpdate,
            }),
    },
];
