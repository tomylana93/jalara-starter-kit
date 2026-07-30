import { mount } from '@vue/test-utils';
import { beforeEach, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import AppHeader from './AppHeader.vue';
import AppMobileNavigation from './AppMobileNavigation.vue';

beforeEach(() => {
    inertiaPageProps.auth = { user: { name: 'Ada Lovelace' } };
    inertiaPageProps.can.manageSettings = true;
});

it('renders header actions and the shared mobile navigation', () => {
    const wrapper = mount(AppHeader);

    expect(wrapper.find('[data-test="appearance-toggle"]').exists()).toBe(true);
    expect(wrapper.findComponent(AppMobileNavigation).exists()).toBe(true);
    expect(wrapper.text()).toContain('navigation.main.dashboard');
    expect(wrapper.text()).toContain('navigation.main.settings');
});

it('hides the header avatar action on mobile', () => {
    const wrapper = mount(AppHeader);

    expect(wrapper.find('.hidden.lg\\:block').exists()).toBe(true);
});
