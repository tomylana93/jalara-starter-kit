<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/NewPasswordController';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(store().url, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset password" />

    <form @submit.prevent="submit">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    autocomplete="email"
                    v-model="form.email"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> Confirm password </Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="form.processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="form.processing" />
                Reset password
            </Button>
        </div>
    </form>
</template>
