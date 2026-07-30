<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/MasterData/UserController';
import PageWrapper from '@/components/PageWrapper.vue';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { create, index } from '@/routes/master-data/users';
import type { SelectOption } from '@/types';
import UserForm from './components/UserForm.vue';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    roleOptions: SelectOption[];
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
                    'master_data.user.create.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: create(),
            },
        ],
    }),
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
