<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    KeyRound,
    Mail,
    MessagesSquare,
    Palette,
    Settings2,
    ShieldCheck,
    UserPlus,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit as editAuthentication } from '@/routes/settings/authentication';
import { edit as editBranding } from '@/routes/settings/branding';
import { edit as editChat } from '@/routes/settings/chat';
import { edit as editGeneral } from '@/routes/settings/general';
import { edit as editMail } from '@/routes/settings/mail';
import { edit as editSecurity } from '@/routes/settings/security';
import { edit as editUserProvisioning } from '@/routes/settings/user-provisioning';
import type { RouteDefinition } from '@/wayfinder';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

type SettingsCard = {
    key: string;
    title: string;
    description: string;
    href: RouteDefinition<'get'>;
    icon: LucideIcon;
};

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'setting.layout.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const { t } = useTranslations();
const settingsCards = computed<SettingsCard[]>(() => [
    {
        key: 'general',
        title: t('setting.general.title'),
        description: t('setting.general.description'),
        href: editGeneral(),
        icon: Settings2,
    },
    {
        key: 'authentication',
        title: t('setting.authentication.title'),
        description: t('setting.authentication.description'),
        href: editAuthentication(),
        icon: KeyRound,
    },
    {
        key: 'user-provisioning',
        title: t('setting.user_provisioning.title'),
        description: t('setting.user_provisioning.description'),
        href: editUserProvisioning(),
        icon: UserPlus,
    },
    {
        key: 'mail',
        title: t('setting.mail.title'),
        description: t('setting.mail.description'),
        href: editMail(),
        icon: Mail,
    },
    {
        key: 'security',
        title: t('setting.security.title'),
        description: t('setting.security.description'),
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        key: 'branding',
        title: t('setting.branding.title'),
        description: t('setting.branding.description'),
        href: editBranding(),
        icon: Palette,
    },
    {
        key: 'chat',
        title: t('setting.chat.title'),
        description: t('setting.chat.description'),
        href: editChat(),
        icon: MessagesSquare,
    },
]);
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.layout.title')" />

        <PageWrapper
            :title="t('setting.layout.title')"
            :description="t('setting.layout.description')"
            content-class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="card in settingsCards"
                :key="card.key"
                :href="card.href"
                :data-test="`settings-card-${card.key}`"
                class="group rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <Card
                    class="h-full gap-4 transition-colors group-hover:bg-accent/50"
                >
                    <CardHeader class="gap-4">
                        <div class="flex items-center justify-between gap-4">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-muted text-muted-foreground transition-colors group-hover:text-foreground"
                            >
                                <component :is="card.icon" class="size-5" />
                            </div>
                            <ArrowRight
                                class="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-foreground"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <CardTitle>{{ card.title }}</CardTitle>
                            <CardDescription>
                                {{ card.description }}
                            </CardDescription>
                        </div>
                    </CardHeader>
                </Card>
            </Link>
        </PageWrapper>
    </div>
</template>
