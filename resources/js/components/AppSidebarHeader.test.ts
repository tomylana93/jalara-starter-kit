import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import AppSidebarHeader from './AppSidebarHeader.vue';

it('renders the appearance toggle in the header actions', () => {
    const wrapper = mount(AppSidebarHeader);

    expect(wrapper.find('[data-test="appearance-toggle"]').exists()).toBe(true);
});

it('keeps the toggle visible alongside breadcrumbs', () => {
    const wrapper = mount(AppSidebarHeader, {
        props: {
            breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
        },
    });

    expect(wrapper.find('[data-test="appearance-toggle"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Dashboard');
});
