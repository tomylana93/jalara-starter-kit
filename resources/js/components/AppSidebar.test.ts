import { mount } from '@vue/test-utils';
import { beforeEach, expect, it, vi } from 'vitest';
import type * as SidebarComponents from '@/components/ui/sidebar';
import { inertiaPageProps } from '@/test/setup';
import AppMobileNavigation from './AppMobileNavigation.vue';
import AppSidebar from './AppSidebar.vue';

const { sidebarState, setOpenMobile } = vi.hoisted(() => ({
    sidebarState: { isMobile: false },
    setOpenMobile: vi.fn(),
}));

vi.mock('@/components/ui/sidebar', async (importOriginal) => ({
    ...(await importOriginal<typeof SidebarComponents>()),
    useSidebar: () => ({
        isMobile: sidebarState.isMobile,
        setOpenMobile,
    }),
}));

beforeEach(() => {
    inertiaPageProps.auth = { user: { name: 'Ada Lovelace' } };
    inertiaPageProps.can.manageSettings = true;
    inertiaPageProps.can.manageBackups = false;
    inertiaPageProps.can.viewUsers = true;
    inertiaPageProps.can.auditChat = false;
    inertiaPageProps.chat.enabled = true;
    sidebarState.isMobile = false;
    setOpenMobile.mockReset();
});

it('includes admin navigation when the user has permission', () => {
    const wrapper = mount(AppSidebar);
    const groups = wrapper.findAllComponents({ name: 'NavMain' });

    expect(
        groups.map((group) => ({
            title: group.props('group'),
            items: group
                .props('items')
                .map((item: { title: string }) => item.title),
        })),
    ).toEqual([
        {
            title: 'navigation.group.main_menu',
            items: ['navigation.main.dashboard', 'navigation.main.chat'],
        },
        {
            title: 'navigation.group.admin',
            items: ['navigation.main.master_data', 'navigation.main.settings'],
        },
    ]);
});

it('hides admin navigation when the user lacks permission', () => {
    inertiaPageProps.can.manageSettings = false;
    inertiaPageProps.can.viewUsers = false;

    const wrapper = mount(AppSidebar);
    const groups = wrapper.findAllComponents({ name: 'NavMain' });

    expect(groups).toHaveLength(1);
    expect(groups[0]?.props('group')).toBe('navigation.group.main_menu');
});

it('uses the shared navigation in the mobile drawer and closes it on navigation', () => {
    sidebarState.isMobile = true;

    const wrapper = mount(AppSidebar);
    const mobileNavigation = wrapper.getComponent(AppMobileNavigation);

    mobileNavigation.vm.$emit('close');

    expect(wrapper.findComponent({ name: 'NavMain' }).exists()).toBe(false);
    expect(setOpenMobile).toHaveBeenCalledWith(false);
});
