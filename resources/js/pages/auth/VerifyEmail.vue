<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verify your email',
        description: 'Open the verification link we sent before continuing.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verify email" />

    <div class="space-y-6 text-center">
        <p class="text-sm text-muted-foreground">
            Check your inbox and follow the verification link. If it did not
            arrive, you can request another email.
        </p>

        <p
            v-if="status === 'verification-link-sent'"
            class="text-sm font-medium text-green-600"
        >
            A new verification link has been sent to your email address.
        </p>

        <Form v-bind="send.form()" v-slot="{ processing }" class="space-y-3">
            <Button class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Resend verification email
            </Button>
        </Form>

        <Form v-bind="logout.form()">
            <Button type="submit" variant="ghost" class="w-full">
                Log out
            </Button>
        </Form>
    </div>
</template>
