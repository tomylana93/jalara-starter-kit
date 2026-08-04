import { usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Database,
    LayoutGrid,
    MessagesSquare,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { index as chatIndex } from '@/routes/chat';
import { index as chatAuditIndex } from '@/routes/chat/audit';
import { index as documentationIndex } from '@/routes/documentation';
import { index as masterDataIndex } from '@/routes/master-data';
import { index as settingsIndex } from '@/routes/settings';
import type { NavItem } from '@/types';

export type AppNavigationGroup = {
    title: string;
    items: NavItem[];
};

export function useAppNavigation() {
    const page = usePage();
    const { t } = useTranslations();

    const mainMenuItems = computed<NavItem[]>(() => {
        const items: NavItem[] = [
            {
                title: t('navigation.main.dashboard'),
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];

        /* The entry disappears entirely while chat is switched off. */
        if (page.props.chat?.enabled) {
            items.push({
                title: t('navigation.main.chat'),
                href: chatIndex(),
                icon: MessagesSquare,
            });
        }

        return items;
    });

    const adminItems = computed<NavItem[]>(() => {
        const items: NavItem[] = [];

        if (page.props.can?.auditChat) {
            items.push({
                title: t('navigation.main.chat_audit'),
                href: chatAuditIndex(),
                icon: ShieldCheck,
            });
        }

        if (page.props.can?.viewUsers) {
            items.push({
                title: t('navigation.main.master_data'),
                href: masterDataIndex(),
                icon: Database,
            });
        }

        if (page.props.can?.manageSettings) {
            items.push({
                title: t('navigation.main.settings'),
                href: settingsIndex(),
                icon: Settings,
            });
        }

        return items;
    });

    const mainItems = computed<NavItem[]>(() => [
        ...mainMenuItems.value,
        ...adminItems.value,
    ]);

    const mainGroups = computed<AppNavigationGroup[]>(() =>
        [
            {
                title: t('navigation.group.main_menu'),
                items: mainMenuItems.value,
            },
            {
                title: t('navigation.group.admin'),
                items: adminItems.value,
            },
        ].filter((group) => group.items.length > 0),
    );

    const footerItems = computed<NavItem[]>(() => [
        {
            title: t('navigation.main.documentation'),
            href: documentationIndex(),
            icon: BookOpen,
        },
    ]);
    const commandItems = computed<NavItem[]>(() => [
        ...mainItems.value,
        ...footerItems.value,
    ]);

    return {
        mainItems,
        mainGroups,
        footerItems,
        commandItems,
    };
}
