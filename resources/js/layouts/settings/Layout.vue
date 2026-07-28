<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { edit as editAuthentication } from '@/routes/settings/authentication';
import { edit as editBranding } from '@/routes/settings/branding';
import { edit as editGeneral } from '@/routes/settings/general';
import { edit as editMail } from '@/routes/settings/mail';
import { edit as editSecurity } from '@/routes/settings/security';
import { edit as editUserProvisioning } from '@/routes/settings/user-provisioning';
import type { NavItem } from '@/types';

const { isCurrentOrParentUrl } = useCurrentUrl();
const { t } = useTranslations();
const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('setting.layout.label.general'),
        href: editGeneral(),
    },
    {
        title: t('setting.layout.label.authentication'),
        href: editAuthentication(),
    },
    {
        title: t('setting.layout.label.user_provisioning'),
        href: editUserProvisioning(),
    },
    {
        title: t('setting.layout.label.mail'),
        href: editMail(),
    },
    {
        title: t('setting.layout.label.security'),
        href: editSecurity(),
    },
    {
        title: t('setting.layout.label.branding'),
        href: editBranding(),
    },
]);
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('setting.layout.title')"
            :description="t('setting.layout.description')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-56">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    :aria-label="t('setting.layout.title')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
