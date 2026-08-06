<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { translate, useTranslations } from '@/composables/useTranslations';
import { errorIcon, errorTranslationKey } from '@/lib/errorStatus';
import { home } from '@/routes';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
    status: number;
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
            `error.${errorTranslationKey(props.status)}.title`,
            props.locale,
            props.fallbackLocale,
        ),
        description: translate(
            `error.${errorTranslationKey(props.status)}.description`,
            props.locale,
            props.fallbackLocale,
        ),
        icon: errorIcon(props.status),
    }),
});

const props = defineProps<{
    status: number;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t(`error.${errorTranslationKey(props.status)}.title`)" />

    <Button class="w-full" as-child>
        <!-- The generated URL, not the definition: an as-child trigger renders
             the bound href straight onto the anchor before Inertia can resolve
             an object. -->
        <Link :href="home().url" data-test="error-home">
            {{ t('error.button.home') }}
        </Link>
    </Button>
</template>
