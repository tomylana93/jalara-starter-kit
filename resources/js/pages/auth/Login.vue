<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import TextLink from '@/components/TextLink.vue';
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
import { t } from '@/lib/i18n';
import { register } from '@/routes';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { request } from '@/routes/password';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

defineOptions({
    layout: {
        title: t('authentication.heading.login', {
            app_name: import.meta.env.VITE_APP_NAME || 'Laravel',
        }),
    },
});

defineProps<{
    canResetPassword: boolean;
    canRegister: boolean;
    status?: string;
}>();

const form = useForm<LoginForm>(store(), {
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submit = (): void => {
    form.submit({
        onSuccess: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="t('authentication.button.login')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

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

            <div class="grid gap-2">
                <InputGroup>
                    <InputGroupAddon>
                        <LockKeyhole />
                    </InputGroupAddon>
                    <InputGroupInput
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        autocomplete="current-password"
                        :placeholder="t('authentication.placeholder.password')"
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
                                                ? 'Hide password'
                                                : 'Show password'
                                        }}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex flex-col gap-4">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('authentication.button.login') }}
                </Button>
                <Button
                    v-if="canResetPassword"
                    :as="Link"
                    :href="request()"
                    variant="ghost"
                >
                    {{ t('authentication.link.forgot_password') }}
                </Button>
            </div>

            <Button
                v-if="canRegister"
                :as="Link"
                :href="register()"
                variant="outline"
            >
                {{ t('authentication.link.register') }}
            </Button>
        </div>
    </form>
</template>
