import { beforeEach, describe, expect, it } from 'vitest';
import { toUrl } from '@/lib/utils';
import { inertiaPageProps } from '@/test/setup';
import { useAppNavigation } from './useAppNavigation';

describe('useAppNavigation', () => {
    beforeEach(() => {
        inertiaPageProps.can.manageSettings = true;
        inertiaPageProps.can.manageBackups = false;
        inertiaPageProps.can.viewUsers = true;
        inertiaPageProps.can.auditChat = false;
        inertiaPageProps.chat.enabled = true;
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
                title: 'navigation.main.chat',
                href: '/chat',
            },
            {
                title: 'navigation.main.master_data',
                href: '/master-data',
            },
            {
                title: 'navigation.main.settings',
                href: '/settings',
            },
        ]);
    });

    it('drops the admin group when no admin permission is held', () => {
        inertiaPageProps.can.manageSettings = false;
        inertiaPageProps.can.viewUsers = false;

        const { mainGroups, mainItems } = useAppNavigation();

        expect(mainItems.value.map((item) => item.title)).toEqual([
            'navigation.main.dashboard',
            'navigation.main.chat',
        ]);
        expect(mainGroups.value.map((group) => group.title)).toEqual([
            'navigation.group.main_menu',
        ]);
    });

    it('shows only settings to a user who cannot view users', () => {
        inertiaPageProps.can.viewUsers = false;

        const { mainItems } = useAppNavigation();

        expect(mainItems.value.map((item) => item.title)).toEqual([
            'navigation.main.dashboard',
            'navigation.main.chat',
            'navigation.main.settings',
        ]);
    });

    it('places master data alongside settings in the admin group', () => {
        const { mainGroups } = useAppNavigation();

        expect(
            mainGroups.value.map((group) => ({
                title: group.title,
                items: group.items.map((item) => item.title),
            })),
        ).toEqual([
            {
                title: 'navigation.group.main_menu',
                items: ['navigation.main.dashboard', 'navigation.main.chat'],
            },
            {
                title: 'navigation.group.admin',
                items: [
                    'navigation.main.master_data',
                    'navigation.main.settings',
                ],
            },
        ]);
    });

    it('shows only master data to a user who cannot manage settings', () => {
        inertiaPageProps.can.manageSettings = false;

        const { mainGroups } = useAppNavigation();

        expect(
            mainGroups.value.map((group) => group.items.map((i) => i.title)),
        ).toEqual([
            ['navigation.main.dashboard', 'navigation.main.chat'],
            ['navigation.main.master_data'],
        ]);
    });

    /*
     * Backups live behind their own ability, and the settings index is the hub
     * that leads to them. A holder of only that ability must still get the entry,
     * or the permission would be grantable but unreachable.
     */
    it('offers the settings entry to a holder of only the backup ability', () => {
        inertiaPageProps.can.manageSettings = false;
        inertiaPageProps.can.viewUsers = false;
        inertiaPageProps.can.manageBackups = true;

        const { mainItems } = useAppNavigation();

        expect(
            mainItems.value.map((item) => ({
                title: item.title,
                href: toUrl(item.href),
            })),
        ).toContainEqual({
            title: 'navigation.main.settings',
            href: '/settings',
        });
    });

    it('drops the settings entry when neither ability is held', () => {
        inertiaPageProps.can.manageSettings = false;
        inertiaPageProps.can.manageBackups = false;

        const { mainItems } = useAppNavigation();

        expect(mainItems.value.map((item) => item.title)).not.toContain(
            'navigation.main.settings',
        );
    });

    it('drops the chat entry while chat is switched off', () => {
        inertiaPageProps.chat.enabled = false;

        const { mainItems } = useAppNavigation();

        expect(mainItems.value.map((item) => item.title)).not.toContain(
            'navigation.main.chat',
        );
    });

    it('offers the chat audit entry only to a Super Admin', () => {
        inertiaPageProps.can.auditChat = true;

        const { mainItems } = useAppNavigation();

        expect(mainItems.value.map((item) => item.title)).toContain(
            'navigation.main.chat_audit',
        );
    });

    it('provides internal documentation as the only footer entry', () => {
        const { footerItems, commandItems } = useAppNavigation();

        expect(
            footerItems.value.map((item) => ({
                title: item.title,
                href: toUrl(item.href),
            })),
        ).toEqual([
            {
                title: 'navigation.main.documentation',
                href: '/documentation',
            },
        ]);
        expect(footerItems.value.map((item) => toUrl(item.href))).not.toContain(
            'https://github.com/tomylana93/jalara-starter-kit',
        );
        expect(commandItems.value.map((item) => item.title)).toContain(
            'navigation.main.documentation',
        );
    });
});
