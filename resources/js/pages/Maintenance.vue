<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Settings2, Wrench } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { translate, useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';
import { edit as securitySettings } from '@/routes/settings/security';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    /*
     * Inertia hands every shared prop to the page component, and this page
     * renders a fragment, so undeclared props would otherwise leak onto the DOM
     * as extraneous attributes.
     */
    inheritAttrs: false,
    layout: (props: LayoutProps) => ({
        title: translate(
            'maintenance.title',
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            'maintenance.description',
            props.locale,
            props.fallbackLocale,
        ),
        icon: Wrench,
    }),
});

const { t } = useTranslations();
const page = usePage();

/* A session survives maintenance, so the sign-out escape has to be offered. */
const isAuthenticated = computed(() => page.props.auth.user !== null);

/*
 * `manage settings` bypasses the maintenance middleware entirely, so this
 * shortcut only ever appears when a 503 reached the page from elsewhere.
 */
const canManageSettings = computed(() => page.props.can.manageSettings);

const retry = () => router.reload();
</script>

<template>
    <Head :title="t('maintenance.title')" />

    <div class="space-y-3">
        <Button class="w-full" data-test="maintenance-retry" @click="retry">
            {{ t('maintenance.button.retry') }}
        </Button>

        <Form
            v-if="isAuthenticated"
            v-bind="logout.form()"
            v-slot="{ processing }"
        >
            <Button
                variant="outline"
                class="w-full"
                :disabled="processing"
                data-test="maintenance-sign-out"
            >
                <Spinner v-if="processing" />
                {{ t('maintenance.button.sign_out') }}
            </Button>
        </Form>

        <Button
            v-if="canManageSettings"
            variant="ghost"
            class="w-full"
            as-child
        >
            <!-- The generated URL, not the definition: an as-child trigger
                 renders the bound href straight onto the anchor before Inertia
                 can resolve an object. -->
            <Link
                :href="securitySettings().url"
                data-test="maintenance-settings"
            >
                <Settings2 class="size-4" />
                {{ t('maintenance.button.settings') }}
            </Link>
        </Button>
    </div>
</template>
