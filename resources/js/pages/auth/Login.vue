<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail } from '@lucide/vue';
import { ref, watchEffect } from 'vue';

import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import InputError from '@/components/form/InputError.vue';
import PasskeyVerify from '@/components/security/passkeys/PasskeyVerify.vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';

import { register } from '@/routes';
import { request } from '@/routes/password';

import { useTrans } from '@/composables/useTrans';

import type { LoginForm } from '@/types';

const { trans } = useTrans();

const showPassword = ref(false);

const form = useForm<LoginForm>(store(), {
    email: '',
    password: '',
    remember: true,
});

const submit = (): void => {
    form.submit({
        onSuccess: () => form.reset('password'),
    });
};

watchEffect(() => {
    setLayoutProps({
        title: trans('authentication.login.heading'),
        description: trans('authentication.login.description'),
    });
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head :title="trans('authentication.login.title')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify
        :label="trans('authentication.login.button.passkey')"
        :loading-label="trans('authentication.login.button.authenticating')"
        :separator="trans('authentication.login.helper.continue_with_email')"
    />

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

            <div class="grid gap-2">
                <InputGroup>
                    <InputGroupAddon>
                        <LockKeyhole />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        :placeholder="trans('user.placeholder.password')"
                        :aria-invalid="form.errors.password ? true : undefined"
                        :aria-describedby="
                            form.errors.password ? 'password-error' : undefined
                        "
                    />
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <InputGroupButton
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="
                                        showPassword
                                            ? trans(
                                                  'authentication.login.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.login.tooltip.show_password',
                                              )
                                    "
                                >
                                    <EyeOff v-if="showPassword" />
                                    <Eye v-else />
                                </InputGroupButton>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>
                                    {{
                                        showPassword
                                            ? trans(
                                                  'authentication.login.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.login.tooltip.show_password',
                                              )
                                    }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </InputGroup>
                <InputError
                    id="password-error"
                    :message="form.errors.password"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="login-button"
            >
                <Spinner v-if="form.processing" />
                {{ trans('authentication.login.button.submit') }}
            </Button>
            <Button
                v-if="canResetPassword"
                variant="ghost"
                :as="Link"
                :href="request()"
            >
                {{ trans('authentication.login.button.forgot_password') }}
            </Button>
        </div>

        <Separator />

        <Button
            v-if="canRegister"
            variant="outline"
            :as="Link"
            :href="register()"
        >
            {{ trans('authentication.login.button.register') }}
        </Button>
    </form>
</template>
