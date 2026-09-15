<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { watchEffect } from 'vue';

import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

import InputError from '@/components/form/InputError.vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';

import { login } from '@/routes';

import { useTrans } from '@/composables/useTrans';

import type { ForgotPasswordForm } from '@/types';

const { trans } = useTrans();

const form = useForm<ForgotPasswordForm>(store(), {
    email: '',
});

const submit = (): void => {
    form.submit();
};

watchEffect(() => {
    setLayoutProps({
        title: trans('authentication.forgot_password.heading'),
        description: trans('authentication.forgot_password.description'),
    });
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="trans('authentication.forgot_password.title')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <form
        class="flex flex-col gap-6"
        novalidate
        @submit.prevent="submit"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <InputGroup>
                    <InputGroupAddon>
                        <Mail />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="email"
                        v-model="form.email"
                        autofocus
                        autocomplete="email"
                        :placeholder="trans('user.placeholder.email')"
                        :aria-invalid="form.errors.email ? true : undefined"
                        :aria-describedby="
                            form.errors.email ? 'email-error' : undefined
                        "
                    />
                </InputGroup>
                <InputError
                    id="email-error"
                    :message="form.errors.email"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="email-password-reset-link-button"
            >
                <Spinner v-if="form.processing" />
                {{ trans('authentication.forgot_password.button.submit') }}
            </Button>
        </div>

        <Separator />

        <Button
            variant="outline"
            :as="Link"
            :href="login()"
        >
            {{ trans('authentication.forgot_password.button.login') }}
        </Button>
    </form>
</template>
