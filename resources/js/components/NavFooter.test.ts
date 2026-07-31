import { BookOpen, FolderGit2 } from '@lucide/vue';
import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import NavFooter from './NavFooter.vue';

it('renders documentation internally and repository links externally', () => {
    const wrapper = mount(NavFooter, {
        props: {
            items: [
                {
                    title: 'Documentation',
                    href: '/documentation',
                    icon: BookOpen,
                },
                {
                    title: 'Repository',
                    href: 'https://example.test/repository',
                    icon: FolderGit2,
                    external: true,
                },
            ],
        },
        global: {
            stubs: {
                SidebarGroup: { template: '<div><slot /></div>' },
                SidebarGroupContent: { template: '<div><slot /></div>' },
                SidebarMenu: { template: '<div><slot /></div>' },
                SidebarMenuItem: { template: '<div><slot /></div>' },
                SidebarMenuButton: { template: '<div><slot /></div>' },
            },
        },
    });

    const documentation = wrapper.get('a[href="/documentation"]');
    const repository = wrapper.get('a[href="https://example.test/repository"]');

    expect(documentation.attributes('target')).toBeUndefined();
    expect(repository.attributes('target')).toBe('_blank');
    expect(repository.attributes('rel')).toBe('noopener noreferrer');
});
