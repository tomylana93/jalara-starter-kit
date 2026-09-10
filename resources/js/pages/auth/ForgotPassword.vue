<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import { t } from '@/lib/i18n';
import { login } from '@/routes';

interface ForgotPasswordForm {
    email: string;
}

defineOptions({
    inheritAttrs: false,
    layout: {
        title: t('authentication.heading.forgot_password'),
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm<ForgotPasswordForm>(store(), {
    email: '',
});

const submit = (): void => {
    form.submit();
};
</script>

<template>
    <Head :title="t('authentication.heading.forgot_password')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <form class="flex flex-col gap-6" @submit.prevent="submit" novalidate>
        <div class="grid gap-6">
            <div class="grid gap-2">
                <InputGroup>
                    <InputGroupAddon>
                        <Mail />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autofocus
                        autocomplete="email"
                        :placeholder="t('authentication.placeholder.email')"
                        :aria-invalid="form.errors.email ? true : undefined"
                    />
                </InputGroup>
                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-4">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('authentication.button.send_reset_link') }}
                </Button>
                <Button :as="Link" :href="login()" variant="ghost">
                    {{ t('authentication.link.back_to_login') }}
                </Button>
            </div>
        </div>
    </form>
</template>
