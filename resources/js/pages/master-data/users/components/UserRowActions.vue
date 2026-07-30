<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/master-data/users';

const props = defineProps<{
    id: string;
    canUpdate: boolean;
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="flex justify-end">
        <!-- With no permitted action there is nothing to open, so no trigger. -->
        <DropdownMenu v-if="props.canUpdate">
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    :aria-label="t('common.table.row_actions')"
                    :data-test="`user-actions-${props.id}`"
                >
                    <EllipsisVertical class="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem as-child>
                    <!-- The generated URL, not the definition: an as-child
                         menu item renders the bound href straight onto the
                         anchor before Inertia can resolve an object. -->
                    <Link
                        :href="edit(props.id).url"
                        :data-test="`edit-user-${props.id}`"
                    >
                        <Pencil class="size-4" />
                        {{ t('master_data.user.button.edit') }}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
