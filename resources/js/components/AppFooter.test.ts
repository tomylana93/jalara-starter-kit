import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import AppFooter from './AppFooter.vue';

describe('AppFooter', () => {
    afterEach(() => {
        inertiaPageProps.branding = {};
    });

    it('renders the released version above the branding footer text', () => {
        const branding: Record<string, unknown> = {
            footerText: 'Jalara, 2026',
        };
        inertiaPageProps.branding = branding;

        const wrapper = mount(AppFooter);

        expect(wrapper.text()).toContain('v0.1.0');
        expect(wrapper.text().indexOf('v0.1.0')).toBeLessThan(
            wrapper.text().indexOf('Jalara, 2026'),
        );
    });

    it('keeps the version visible without branding footer text and never links it', () => {
        const wrapper = mount(AppFooter);

        expect(wrapper.text()).toContain('v0.1.0');
        expect(wrapper.find('a').exists()).toBe(false);
    });
});
