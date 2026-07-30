<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/MasterData/UserController';
import PageWrapper from '@/components/PageWrapper.vue';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { edit, index } from '@/routes/master-data/users';
import type { SelectOption } from '@/types';
import UserForm from './components/UserForm.vue';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
    user: { id: string };
};

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    status: string;
    role: string | null;
};

const props = defineProps<{
    user: ManagedUser;
    roleOptions: SelectOption[];
    statusOptions: SelectOption[];
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'master_data.layout.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: masterDataIndex(),
            },
            {
                title: translate(
                    'master_data.user.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
            {
                title: translate(
                    'master_data.user.edit.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(layoutProps.user.id),
            },
        ],
    }),
});

const { t } = useTranslations();
</script>

<template>
    <div class="contents">
        <Head :title="t('master_data.user.edit.title')" />

        <PageWrapper
            :title="t('master_data.user.edit.title')"
            :description="t('master_data.user.edit.description')"
        >
            <UserForm
                :action="UserController.update.form(props.user.id)"
                :role-options="props.roleOptions"
                :status-options="props.statusOptions"
                :user="props.user"
                :cancel-href="index()"
                :submit-label="t('master_data.user.button.save')"
            />
        </PageWrapper>
    </div>
</template>
