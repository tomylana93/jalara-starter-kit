<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import SecurityController from '@/actions/App/Http/Controllers/Account/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { translate, useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/account/security';

type Props = {
    passwordRules: string;
    mustChangePassword: boolean;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<Props>();

defineOptions({
    layout: (props: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'account.layout.label.security',
                    props.locale,
                    props.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('account.layout.label.security')" />

    <h1 class="sr-only">{{ t('account.layout.label.security') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.security.title')"
            :description="t('account.security.description')"
        />

        <Alert v-if="props.mustChangePassword">
            <TriangleAlert class="size-4" />
            <AlertTitle>
                {{ t('account.security.message.must_change_password_title') }}
            </AlertTitle>
            <AlertDescription>
                {{ t('account.security.message.must_change_password') }}
            </AlertDescription>
        </Alert>

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">
                    {{ t('account.security.label.current_password') }}
                </Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    :aria-invalid="Boolean(errors.current_password)"
                    autocomplete="current-password"
                    :placeholder="
                        t('account.security.placeholder.current_password')
                    "
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">
                    {{ t('account.security.label.password') }}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    :aria-invalid="Boolean(errors.password)"
                    autocomplete="new-password"
                    :placeholder="t('account.security.placeholder.password')"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">
                    {{ t('account.security.label.password_confirmation') }}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    autocomplete="new-password"
                    :placeholder="
                        t('account.security.placeholder.password_confirmation')
                    "
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    {{ t('account.security.button.save') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
