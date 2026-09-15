<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail } from '@lucide/vue';
import { ref, watchEffect } from 'vue';

import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import InputError from '@/components/form/InputError.vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/NewPasswordController';

import { useTrans } from '@/composables/useTrans';

import type { ResetPasswordForm } from '@/types';

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const { trans } = useTrans();

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm<ResetPasswordForm>(store(), {
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.submit({
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};

watchEffect(() => {
    setLayoutProps({
        title: trans('authentication.reset_password.heading'),
        description: trans('authentication.reset_password.description'),
    });
});
</script>

<template>
    <Head :title="trans('authentication.reset_password.title')" />

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
                        autocomplete="email"
                        readonly
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
                        autofocus
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
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
                                                  'authentication.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.tooltip.show_password',
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
                                                  'authentication.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.tooltip.show_password',
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

            <div class="grid gap-2">
                <InputGroup>
                    <InputGroupAddon>
                        <LockKeyhole />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        :placeholder="
                            trans('user.placeholder.password_confirmation')
                        "
                        :aria-invalid="
                            form.errors.password_confirmation ? true : undefined
                        "
                        :aria-describedby="
                            form.errors.password_confirmation
                                ? 'password-confirmation-error'
                                : undefined
                        "
                    />
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <InputGroupButton
                                    type="button"
                                    @click="
                                        showPasswordConfirmation =
                                            !showPasswordConfirmation
                                    "
                                    :aria-label="
                                        showPasswordConfirmation
                                            ? trans(
                                                  'authentication.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.tooltip.show_password',
                                              )
                                    "
                                >
                                    <EyeOff v-if="showPasswordConfirmation" />
                                    <Eye v-else />
                                </InputGroupButton>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>
                                    {{
                                        showPasswordConfirmation
                                            ? trans(
                                                  'authentication.tooltip.hide_password',
                                              )
                                            : trans(
                                                  'authentication.tooltip.show_password',
                                              )
                                    }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </InputGroup>
                <InputError
                    id="password-confirmation-error"
                    :message="form.errors.password_confirmation"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="form.processing" />
                {{ trans('authentication.reset_password.button.submit') }}
            </Button>
        </div>
    </form>
</template>
