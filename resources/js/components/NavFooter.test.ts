import { BookOpen } from '@lucide/vue';
import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import { inertiaPageUrl } from '@/test/setup';
import NavFooter from './NavFooter.vue';

/*
 * `useCurrentUrl` derives the current path from a module-level computed that is
 * only evaluated once, so the stub url is fixed for the whole file.
 */
inertiaPageUrl.value = '/documentation';

const mountNavFooter = () =>
    mount(NavFooter, {
        props: {
            items: [
                {
                    title: 'Documentation',
                    href: '/documentation',
                    icon: BookOpen,
                },
            ],
        },
        global: {
            stubs: {
                SidebarGroup: { template: '<section><slot /></section>' },
                SidebarGroupContent: { template: '<div><slot /></div>' },
                SidebarMenu: { template: '<div><slot /></div>' },
                SidebarMenuButton: {
                    props: ['tooltip', 'isActive'],
                    template:
                        '<div :data-tooltip="tooltip" :data-active="isActive"><slot /></div>',
                },
                SidebarMenuItem: { template: '<div><slot /></div>' },
            },
        },
    });

it('passes the item title as the collapsed sidebar tooltip', () => {
    const wrapper = mountNavFooter();

    expect(wrapper.get('[data-tooltip]').attributes('data-tooltip')).toBe(
        'Documentation',
    );
    expect(wrapper.text()).toContain('Documentation');
});

it('marks the item active while its page is current', () => {
    const wrapper = mountNavFooter();

    expect(wrapper.get('[data-tooltip]').attributes('data-active')).toBe(
        'true',
    );
});
