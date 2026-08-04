import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { inertiaPageProps, inertiaPageUrl } from '@/test/setup';
import AppMobileNavigation from './AppMobileNavigation.vue';
import UserMenuContent from './UserMenuContent.vue';

describe('AppMobileNavigation', () => {
    beforeEach(() => {
        inertiaPageProps.auth = {
            user: {
                name: 'Ada Lovelace',
            },
        };
        inertiaPageProps.can.manageSettings = true;
        inertiaPageUrl.value = '/dashboard';
    });

    it('renders the complete navigation without any external link', () => {
        const wrapper = mount(AppMobileNavigation);

        expect(wrapper.text()).toContain('navigation.main.dashboard');
        expect(wrapper.text()).toContain('navigation.main.settings');
        expect(wrapper.text()).not.toContain('navigation.group.main_menu');
        expect(wrapper.text()).not.toContain('navigation.group.admin');
        expect(wrapper.text()).not.toContain('navigation.external.repository');
        expect(wrapper.text()).toContain('navigation.main.documentation');
        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.get('[aria-current="page"]').text()).toContain(
            'navigation.main.dashboard',
        );
        expect(wrapper.findAll('a[target="_blank"]')).toHaveLength(0);
        expect(wrapper.findComponent(UserMenuContent).exists()).toBe(true);
    });

    it('requests drawer closure after every navigation choice', async () => {
        const wrapper = mount(AppMobileNavigation);

        const mainLink = wrapper.get('nav a');
        mainLink.element.addEventListener('click', (event) =>
            event.preventDefault(),
        );
        await mainLink.trigger('click');
        wrapper.findComponent(UserMenuContent).vm.$emit('navigate');
        await wrapper
            .get('[data-test="mobile-navigation-close"]')
            .trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(3);
    });
});
