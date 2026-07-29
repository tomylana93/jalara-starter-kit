<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Account/ProfileController';
import DisableAccount from '@/components/DisableAccount.vue';
import Heading from '@/components/Heading.vue';
import ImageUploadField from '@/components/ImageUploadField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import UploadGuardOverlay from '@/components/UploadGuardOverlay.vue';
import { useInitials } from '@/composables/useInitials';
import { translate, useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/account/profile';
import {
    destroy as avatarDestroy,
    store as avatarStore,
} from '@/routes/account/profile/avatar';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineProps<{
    canDisableAccount: boolean;
}>();

defineOptions({
    layout: (props: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'account.profile.title',
                    props.locale,
                    props.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useTranslations();
const { getInitials } = useInitials();
</script>

<template>
    <Head :title="t('account.profile.title')" />

    <h1 class="sr-only">{{ t('account.profile.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('account.profile.title')"
            :description="t('account.profile.description')"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing, validate }"
        >
            <!--
                The avatar sits in the profile form for layout, but posts to its
                own endpoint: the input carries no name, so a pending file never
                travels with the name and email submission.
            -->
            <ImageUploadField
                :label="t('account.profile.label.avatar')"
                :current-url="user.avatar"
                :upload-url="avatarStore().url"
                test-id="profile-avatar"
                :delete-url="avatarDestroy().url"
                shape="circle"
                :fallback-text="getInitials(user.name)"
            />

            <div class="grid gap-2">
                <Label for="name">{{ t('account.profile.label.name') }}</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="user.name"
                    :aria-invalid="Boolean(errors.name)"
                    autocomplete="name"
                    :placeholder="t('account.profile.placeholder.name')"
                    @change="validate('name')"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{
                    t('account.profile.label.email')
                }}</Label>
                <Input
                    id="email"
                    type="text"
                    inputmode="email"
                    name="email"
                    :default-value="user.email"
                    :aria-invalid="Boolean(errors.email)"
                    autocomplete="username"
                    :placeholder="t('account.profile.placeholder.email')"
                    @change="validate('email')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    {{ t('account.profile.button.save') }}
                </Button>
            </div>
        </Form>
    </div>

    <DisableAccount v-if="canDisableAccount" />

    <UploadGuardOverlay />
</template>
