<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { buttonVariants } from '@/components/ui/button';
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
        <Link
            v-if="props.canUpdate"
            :href="edit(props.id)"
            :class="buttonVariants({ variant: 'ghost', size: 'sm' })"
            :data-test="`edit-user-${props.id}`"
        >
            <Pencil class="size-4" />
            {{ t('master_data.user.button.edit') }}
        </Link>
    </div>
</template>
