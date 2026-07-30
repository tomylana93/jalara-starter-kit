import { usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Database,
    FolderGit2,
    LayoutGrid,
    Settings,
} from '@lucide/vue';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
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

    const mainMenuItems = computed<NavItem[]>(() => [
        {
            title: t('navigation.main.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
    ]);

    const adminItems = computed<NavItem[]>(() => {
        const items: NavItem[] = [];

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

    const externalItems = computed<NavItem[]>(() => [
        {
            title: t('navigation.external.repository'),
            href: 'https://github.com/laravel/vue-starter-kit',
            icon: FolderGit2,
        },
        {
            title: t('navigation.external.documentation'),
            href: 'https://laravel.com/docs/starter-kits#vue',
            icon: BookOpen,
        },
    ]);

    return {
        mainItems,
        mainGroups,
        externalItems,
    };
}
