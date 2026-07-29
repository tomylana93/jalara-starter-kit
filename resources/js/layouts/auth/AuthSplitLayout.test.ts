import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import AuthSplitLayout from './AuthSplitLayout.vue';

const mountLayout = () =>
    mount(AuthSplitLayout, {
        props: { title: 'Log in' },
        global: {
            stubs: {
                BrandIdentity: { template: '<div data-test="brand" />' },
                AppFooter: { template: '<footer />' },
            },
        },
    });

afterEach(() => {
    inertiaPageProps.name = undefined;
    inertiaPageProps.description = null;
});

describe('auth split layout', () => {
    it('uses a graduated brand tint over the background image', () => {
        const tint = mountLayout().get('[data-test="auth-split-tint"]');

        expect(tint.classes()).toEqual(
            expect.arrayContaining([
                'bg-linear-to-b',
                'from-primary/80',
                'via-primary/55',
                'to-primary/80',
            ]),
        );
        expect(tint.classes()).not.toContain('bg-primary/70');
    });

    it('shows the application description at the foot of the panel', () => {
        inertiaPageProps.name = 'Jalara App';
        inertiaPageProps.description = 'Operational starter kit';

        const about = mountLayout().get('[data-test="auth-split-about"]');

        expect(about.text()).toBe('Operational starter kit');
        expect(about.text()).not.toContain('Jalara App');
        expect(about.classes()).toContain('mt-auto');
    });

    it('renders nothing when no description is configured', () => {
        inertiaPageProps.name = 'Jalara App';

        const wrapper = mountLayout();

        expect(wrapper.find('[data-test="auth-split-about"]').exists()).toBe(
            false,
        );
    });
});
