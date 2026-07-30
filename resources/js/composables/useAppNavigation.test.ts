import { FolderGit2 } from '@lucide/vue';
import { beforeEach, describe, expect, it } from 'vitest';
import { toUrl } from '@/lib/utils';
import { inertiaPageProps } from '@/test/setup';
import { useAppNavigation } from './useAppNavigation';

describe('useAppNavigation', () => {
    beforeEach(() => {
        inertiaPageProps.can.manageSettings = true;
    });

    it('provides translated main navigation using Wayfinder routes', () => {
        const { mainItems } = useAppNavigation();

        expect(
            mainItems.value.map((item) => ({
                title: item.title,
                href: toUrl(item.href),
            })),
        ).toEqual([
            {
                title: 'navigation.main.dashboard',
                href: '/dashboard',
            },
            {
                title: 'navigation.main.settings',
                href: '/settings',
            },
        ]);
    });

    it('reactively filters settings by permission', () => {
        const { mainGroups, mainItems } = useAppNavigation();

        inertiaPageProps.can.manageSettings = false;

        expect(mainItems.value.map((item) => item.title)).toEqual([
            'navigation.main.dashboard',
        ]);
        expect(mainGroups.value.map((group) => group.title)).toEqual([
            'navigation.group.main_menu',
        ]);
    });

    it('places settings in its own admin group', () => {
        const { mainGroups } = useAppNavigation();

        expect(
            mainGroups.value.map((group) => ({
                title: group.title,
                items: group.items.map((item) => item.title),
            })),
        ).toEqual([
            {
                title: 'navigation.group.main_menu',
                items: ['navigation.main.dashboard'],
            },
            {
                title: 'navigation.group.admin',
                items: ['navigation.main.settings'],
            },
        ]);
    });

    it('provides the canonical external navigation', () => {
        const { externalItems } = useAppNavigation();

        expect(
            externalItems.value.map((item) => ({
                title: item.title,
                href: toUrl(item.href),
            })),
        ).toEqual([
            {
                title: 'navigation.external.repository',
                href: 'https://github.com/laravel/vue-starter-kit',
            },
            {
                title: 'navigation.external.documentation',
                href: 'https://laravel.com/docs/starter-kits#vue',
            },
        ]);
        expect(externalItems.value[0]?.icon).toBe(FolderGit2);
    });
});
