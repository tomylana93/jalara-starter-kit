<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { translate, useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    layout: (props: LayoutProps) => ({
        title: translate(
            'auth.verify_email.title',
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            'auth.verify_email.description',
            props.locale,
            props.fallbackLocale,
        ),
    }),
});

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('auth.verify_email.title')" />

    <div class="space-y-6 text-center">
        <p class="text-sm text-muted-foreground">
            {{ t('auth.verify_email.message.instructions') }}
        </p>

        <p
            v-if="status === 'verification-link-sent'"
            class="text-sm font-medium text-green-600"
        >
            {{ t('auth.verify_email.message.sent') }}
        </p>

        <Form v-bind="send.form()" v-slot="{ processing }" class="space-y-3">
            <Button class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                {{ t('auth.verify_email.button.resend') }}
            </Button>
        </Form>

        <Form v-bind="logout.form()">
            <Button type="submit" variant="ghost" class="w-full">
                {{ t('auth.session.button.logout') }}
            </Button>
        </Form>
    </div>
</template>
