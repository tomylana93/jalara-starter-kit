<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { translate, useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import { email } from '@/routes/password';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    layout: (props: LayoutProps) => ({
        title: translate(
            'auth.forgot_password.title',
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            'auth.forgot_password.description',
            props.locale,
            props.fallbackLocale,
        ),
    }),
});

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('auth.forgot_password.title')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">
                    {{ t('auth.forgot_password.label.email') }}
                </Label>
                <Input
                    id="email"
                    type="text"
                    inputmode="email"
                    name="email"
                    :aria-invalid="Boolean(errors.email)"
                    autocomplete="off"
                    autofocus
                    :placeholder="t('auth.forgot_password.placeholder.email')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.forgot_password.button.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('auth.forgot_password.link.return') }}</span>
            <TextLink :href="login()">
                {{ t('auth.forgot_password.link.login') }}
            </TextLink>
        </div>
    </div>
</template>
