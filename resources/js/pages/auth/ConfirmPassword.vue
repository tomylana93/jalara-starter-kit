<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { translate, useTranslations } from '@/composables/useTranslations';
import { store } from '@/routes/password/confirm';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    /*
     * Inertia hands every shared prop to the page component, and these pages
     * render a fragment, so undeclared props would otherwise leak onto the DOM
     * as extraneous attributes.
     */
    inheritAttrs: false,
    layout: (props: LayoutProps) => ({
        title: translate(
            'auth.confirm_password.title',
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            'auth.confirm_password.description',
            props.locale,
            props.fallbackLocale,
        ),
    }),
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('auth.confirm_password.title')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">
                    {{ t('auth.confirm_password.label.password') }}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    :aria-invalid="Boolean(errors.password)"
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.confirm_password.button.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
