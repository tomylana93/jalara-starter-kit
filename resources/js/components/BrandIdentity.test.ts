import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import BrandIdentity from './BrandIdentity.vue';

const withBranding = (branding: Record<string, unknown>) => {
    inertiaPageProps.name = 'Jalara App';
    inertiaPageProps.branding = {
        companyName: 'Jalara Group',
        identityMode: 'icon-text',
        logoUrl: null,
        logoDarkUrl: null,
        iconUrl: null,
        iconDarkUrl: null,
        authBackgroundUrl: null,
        ...branding,
    };
};

afterEach(() => {
    inertiaPageProps.branding = {};
    inertiaPageProps.name = undefined;
});

describe('brand identity', () => {
    it('renders the logo alone in logo mode', () => {
        withBranding({ identityMode: 'logo', logoUrl: '/storage/logo.png' });

        const wrapper = mount(BrandIdentity);

        expect(wrapper.get('img').attributes('src')).toBe('/storage/logo.png');
        expect(wrapper.text()).not.toContain('Jalara App');
    });

    it('renders the icon next to the application name in icon mode', () => {
        withBranding({ iconUrl: '/storage/icon.png' });

        const wrapper = mount(BrandIdentity);

        expect(wrapper.get('img').attributes('src')).toBe('/storage/icon.png');
        expect(wrapper.text()).toContain('Jalara App');
        expect(wrapper.text()).not.toContain('Jalara Group');
    });

    it('falls back to the light asset when no dark variant is stored', () => {
        withBranding({ identityMode: 'logo', logoUrl: '/storage/logo.png' });

        const images = mount(BrandIdentity).findAll('img');

        expect(images).toHaveLength(1);
        expect(images[0].classes()).not.toContain('dark:hidden');
    });

    it('renders both variants so the dark asset is used in dark mode', () => {
        withBranding({
            identityMode: 'logo',
            logoUrl: '/storage/logo.png',
            logoDarkUrl: '/storage/logo-dark.png',
        });

        const images = mount(BrandIdentity).findAll('img');

        expect(images).toHaveLength(2);
        expect(images[0].classes()).toContain('dark:hidden');
        expect(images[1].classes()).toContain('dark:block');
        expect(images[1].attributes('src')).toBe('/storage/logo-dark.png');
    });

    it('falls back to the bundled logo when logo mode has none stored', () => {
        withBranding({ identityMode: 'logo' });

        const images = mount(BrandIdentity).findAll('img');

        expect(images[0].attributes('src')).toBe(
            '/assets/images/branding/logo.png',
        );
        expect(images[1].attributes('src')).toBe(
            '/assets/images/branding/logo-dark.png',
        );
    });

    it('falls back to the bundled icon when icon mode has none stored', () => {
        withBranding({ iconUrl: null });

        const images = mount(BrandIdentity).findAll('img');

        expect(images[0].attributes('src')).toBe(
            '/assets/images/branding/icon.png',
        );
        expect(images[1].attributes('src')).toBe(
            '/assets/images/branding/icon-dark.png',
        );
        expect(mount(BrandIdentity).text()).toContain('Jalara App');
    });

    it('keeps a stored light asset in both modes rather than the bundled dark one', () => {
        withBranding({ iconUrl: '/storage/icon.png' });

        const images = mount(BrandIdentity).findAll('img');

        expect(images).toHaveLength(1);
        expect(images[0].attributes('src')).toBe('/storage/icon.png');
    });

    it('shows the mark alone when compact', () => {
        withBranding({ iconUrl: '/storage/icon.png' });

        const wrapper = mount(BrandIdentity, {
            props: { iconOnly: true, hideName: true },
        });

        expect(wrapper.get('img').attributes('src')).toBe('/storage/icon.png');
        expect(wrapper.text()).not.toContain('Jalara App');
    });

    it('prefers the icon over the logo on compact surfaces', () => {
        withBranding({
            identityMode: 'logo',
            logoUrl: '/storage/logo.png',
            iconUrl: '/storage/icon.png',
        });

        const wrapper = mount(BrandIdentity, { props: { iconOnly: true } });

        expect(wrapper.get('img').attributes('src')).toBe('/storage/icon.png');
    });
});
