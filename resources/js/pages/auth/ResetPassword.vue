<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { translate, useTranslations } from '@/composables/useTranslations';
import { update } from '@/routes/password';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    layout: (props: LayoutProps) => ({
        title: translate(
            'auth.reset_password.title',
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            'auth.reset_password.description',
            props.locale,
            props.fallbackLocale,
        ),
    }),
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
const { t } = useTranslations();
</script>

<template>
    <Head :title="t('auth.reset_password.title')" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">
                    {{ t('auth.reset_password.label.email') }}
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError :message="errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">
                    {{ t('auth.reset_password.label.password') }}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
                    :placeholder="t('auth.reset_password.placeholder.password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">
                    {{ t('auth.reset_password.label.password_confirmation') }}
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    :placeholder="
                        t(
                            'auth.reset_password.placeholder.password_confirmation',
                        )
                    "
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.reset_password.button.submit') }}
            </Button>
        </div>
    </Form>
</template>
