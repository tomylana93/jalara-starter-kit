import { LayoutGrid } from '@lucide/vue';
import { mount } from '@vue/test-utils';
import { beforeEach, expect, it, vi } from 'vitest';
import type * as SidebarComponents from '@/components/ui/sidebar';
import NavMain from './NavMain.vue';

const { sidebarState } = vi.hoisted(() => ({
    sidebarState: { state: 'expanded' },
}));

vi.mock('@/components/ui/sidebar', async (importOriginal) => ({
    ...(await importOriginal<typeof SidebarComponents>()),
    useSidebar: () => ({
        state: sidebarState.state,
    }),
}));

beforeEach(() => {
    sidebarState.state = 'expanded';
});

const mountNavMain = () =>
    mount(NavMain, {
        props: {
            group: 'Workspace',
            items: [
                {
                    title: 'Dashboard',
                    href: '/dashboard',
                    icon: LayoutGrid,
                },
            ],
        },
        global: {
            stubs: {
                SidebarGroup: { template: '<section><slot /></section>' },
                SidebarGroupLabel: { template: '<h2><slot /></h2>' },
                SidebarMenu: { template: '<div><slot /></div>' },
                SidebarMenuButton: { template: '<div><slot /></div>' },
                SidebarMenuItem: { template: '<div><slot /></div>' },
            },
        },
    });

it('renders the supplied navigation group', () => {
    const wrapper = mountNavMain();

    expect(wrapper.get('h2').text()).toBe('Workspace');
    expect(wrapper.text()).toContain('Dashboard');
});

it('does not render the group when the sidebar is collapsed', () => {
    sidebarState.state = 'collapsed';

    const wrapper = mountNavMain();

    expect(wrapper.find('h2').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Workspace');
    expect(wrapper.text()).toContain('Dashboard');
});
