<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/EmailVerificationNotificationController';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = (): void => {
    form.post(store().url);
};
</script>

<template>
    <Head title="Email verification" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        A new verification link has been sent to the email address you provided
        during registration.
    </div>

    <form class="space-y-6 text-center" @submit.prevent="submit">
        <Button type="submit" :disabled="form.processing" variant="secondary">
            <Spinner v-if="form.processing" />
            Resend verification email
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Log out
        </TextLink>
    </form>
</template>
