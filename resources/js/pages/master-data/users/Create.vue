<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/MasterData/UserController';
import PageWrapper from '@/components/PageWrapper.vue';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as masterDataIndex } from '@/routes/master-data';
import { create, index } from '@/routes/master-data/users';
import type { SelectOption } from '@/types';
import UserForm from './components/UserForm.vue';

const props = defineProps<{
    roleOptions: SelectOption[];
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'master_data.layout.title', href: masterDataIndex() },
        { key: 'master_data.user.title', href: index() },
        { key: 'master_data.user.create.title', href: create() },
    ]),
});

const { t } = useTranslations();
</script>

<template>
    <div class="contents">
        <Head :title="t('master_data.user.create.title')" />

        <PageWrapper
            :title="t('master_data.user.create.title')"
            :description="t('master_data.user.create.description')"
        >
            <UserForm
                :action="UserController.store.form()"
                :role-options="props.roleOptions"
                :cancel-href="index()"
                :submit-label="t('master_data.user.button.save')"
            />
        </PageWrapper>
    </div>
</template>
