<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail, User } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { t } from '@/lib/i18n';
import { login } from '@/routes';

interface RegisterForm {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

defineOptions({
    inheritAttrs: false,
    layout: {
        title: t('authentication.heading.register', {
            app_name: import.meta.env.VITE_APP_NAME || 'Laravel',
        }),
    },
});

defineProps<{
    passwordRules: string;
}>();

const form = useForm<RegisterForm>(store(), {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = (): void => {
    form.submit({
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('authentication.button.register')" />

    <form class="flex flex-col gap-6" @submit.prevent="submit" novalidate>
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{{ t('authentication.label.name') }}</Label>
                <InputGroup>
                    <InputGroupAddon>
                        <User />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        name="name"
                        autofocus
                        autocomplete="name"
                        :placeholder="t('authentication.placeholder.name')"
                        :aria-invalid="form.errors.name ? true : undefined"
                    />
                </InputGroup>
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('authentication.label.email') }}</Label>
                <InputGroup>
                    <InputGroupAddon>
                        <Mail />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        :placeholder="t('authentication.placeholder.email')"
                        :aria-invalid="form.errors.email ? true : undefined"
                    />
                </InputGroup>
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{
                    t('authentication.label.password')
                }}</Label>
                <InputGroup>
                    <InputGroupAddon>
                        <LockKeyhole />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        autocomplete="new-password"
                        :placeholder="t('authentication.placeholder.password')"
                        :passwordrules="passwordRules"
                        :aria-invalid="form.errors.password ? true : undefined"
                    />
                    <InputGroupAddon align="inline-end">
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <InputGroupButton
                                        type="button"
                                        size="icon-xs"
                                        :aria-label="
                                            showPassword
                                                ? t(
                                                      'authentication.button.hide_password',
                                                  )
                                                : t(
                                                      'authentication.button.show_password',
                                                  )
                                        "
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" />
                                        <Eye v-else />
                                    </InputGroupButton>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>
                                        {{
                                            showPassword
                                                ? t(
                                                      'authentication.button.hide_password',
                                                  )
                                                : t(
                                                      'authentication.button.show_password',
                                                  )
                                        }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('authentication.label.confirm_password')
                }}</Label>
                <InputGroup>
                    <InputGroupAddon>
                        <LockKeyhole />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        name="password_confirmation"
                        autocomplete="new-password"
                        :placeholder="
                            t('authentication.placeholder.confirm_password')
                        "
                        :passwordrules="passwordRules"
                        :aria-invalid="
                            form.errors.password_confirmation ? true : undefined
                        "
                    />
                    <InputGroupAddon align="inline-end">
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <InputGroupButton
                                        type="button"
                                        size="icon-xs"
                                        :aria-label="
                                            showPasswordConfirmation
                                                ? t(
                                                      'authentication.button.hide_password',
                                                  )
                                                : t(
                                                      'authentication.button.show_password',
                                                  )
                                        "
                                        @click="
                                            showPasswordConfirmation =
                                                !showPasswordConfirmation
                                        "
                                    >
                                        <EyeOff
                                            v-if="showPasswordConfirmation"
                                        />
                                        <Eye v-else />
                                    </InputGroupButton>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>
                                        {{
                                            showPasswordConfirmation
                                                ? t(
                                                      'authentication.button.hide_password',
                                                  )
                                                : t(
                                                      'authentication.button.show_password',
                                                  )
                                        }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div class="flex flex-col gap-4">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('authentication.button.register') }}
                </Button>
                <Button :as="Link" :href="login()" variant="ghost">
                    {{ t('authentication.link.back_to_login') }}
                </Button>
            </div>
        </div>
    </form>
</template>
