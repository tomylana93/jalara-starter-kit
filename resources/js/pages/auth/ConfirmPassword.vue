<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store as confirmStore } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import { store as confirmPassword } from '@/actions/Laravel/Fortify/Http/Controllers/ConfirmablePasswordController';
import { index as confirmOptions } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import { t } from '@/lib/i18n';

defineOptions({
    layout: {
        title: 'Confirm password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    },
});

const form = useForm({ password: '' });

const submit = (): void => {
    form.post(confirmPassword().url, {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Confirm password" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        :label="t('authentication.button.passkey_confirm')"
        :loading-label="t('authentication.button.passkey_confirming')"
        :separator="t('authentication.helper.or_confirm_with_password')"
    />

    <form @submit.prevent="submit">
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">Password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    type="submit"
                    :disabled="form.processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="form.processing" />
                    Confirm password
                </Button>
            </div>
        </div>
    </form>
</template>
