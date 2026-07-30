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

    it('renders the complete navigation with active and safe external links', () => {
        const wrapper = mount(AppMobileNavigation);

        expect(wrapper.text()).toContain('navigation.main.dashboard');
        expect(wrapper.text()).toContain('navigation.main.settings');
        expect(wrapper.text()).not.toContain('navigation.group.main_menu');
        expect(wrapper.text()).not.toContain('navigation.group.admin');
        expect(wrapper.text()).toContain('navigation.external.repository');
        expect(wrapper.text()).toContain('navigation.external.documentation');
        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.get('[aria-current="page"]').text()).toContain(
            'navigation.main.dashboard',
        );

        const externalLinks = wrapper.findAll('a[target="_blank"]');

        expect(externalLinks).toHaveLength(2);
        externalLinks.forEach((link) => {
            expect(link.attributes('rel')).toBe('noopener noreferrer');
        });
        expect(wrapper.findComponent(UserMenuContent).exists()).toBe(true);
    });

    it('requests drawer closure after every navigation choice', async () => {
        const wrapper = mount(AppMobileNavigation);

        const mainLink = wrapper.get('nav a');
        mainLink.element.addEventListener('click', (event) =>
            event.preventDefault(),
        );
        await mainLink.trigger('click');
        const externalLink = wrapper.get('a[target="_blank"]');
        externalLink.element.addEventListener('click', (event) =>
            event.preventDefault(),
        );
        await externalLink.trigger('click');
        wrapper.findComponent(UserMenuContent).vm.$emit('navigate');
        await wrapper
            .get('[data-test="mobile-navigation-close"]')
            .trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(4);
    });
});
