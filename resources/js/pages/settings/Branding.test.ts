import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Branding from './Branding.vue';

const option = (value: string) => [{ value, label: value }];
const props = {
    settings: {
        companyName: 'Jalara',
        footerText: null,
        identityMode: 'icon-text',
        authLayout: 'simple',
        appLayout: 'sidebar',
        colorTheme: 'neutral',
        fontPair: 'instrument-sans',
    },
    identityModeOptions: option('icon-text'),
    authLayoutOptions: option('simple'),
    appLayoutOptions: option('sidebar'),
    colorThemeOptions: ['neutral', 'teal'].map((value) => ({
        value,
        label: value,
    })),
    fontPairOptions: option('instrument-sans'),
};

describe('branding settings form', () => {
    beforeEach(resetFormState);

    it('synchronizes every radio group with a hidden input', async () => {
        const wrapper = mount(Branding, { props });
        const groups = wrapper.findAllComponents({ name: 'RadioGroup' });

        await groups[3].vm.$emit('update:modelValue', 'emerald');
        await groups[4].vm.$emit('update:modelValue', 'poppins-inter');

        expect(
            wrapper.get('input[name="identityMode"]').attributes('value'),
        ).toBe('icon-text');
        expect(
            wrapper.get('input[name="authLayout"]').attributes('value'),
        ).toBe('simple');
        expect(wrapper.get('input[name="appLayout"]').attributes('value')).toBe(
            'sidebar',
        );
        expect(
            wrapper.get('input[name="colorTheme"]').attributes('value'),
        ).toBe('emerald');
        expect(wrapper.get('input[name="fontPair"]').attributes('value')).toBe(
            'poppins-inter',
        );
    });

    it('renders radio errors and disables the submit button while processing', () => {
        formState.errors = { colorTheme: 'Invalid theme' };
        formState.processing = true;

        const wrapper = mount(Branding, { props });

        expect(
            wrapper.get('#colorTheme-neutral').attributes('aria-invalid'),
        ).toBe('true');
        expect(wrapper.text()).toContain('Invalid theme');
        expect(
            wrapper
                .get('[data-test="update-branding-settings-button"]')
                .attributes(),
        ).toHaveProperty('disabled');
    });

    it('previews a draft color theme without changing the document theme', async () => {
        document.documentElement.dataset.colorTheme = 'neutral';
        const wrapper = mount(Branding, { props });
        const groups = wrapper.findAllComponents({ name: 'RadioGroup' });

        await groups[3].vm.$emit('update:modelValue', 'teal');

        for (const preview of [
            'identity-preview',
            'auth-preview',
            'app-preview',
        ]) {
            for (const element of wrapper.findAll(`[data-test="${preview}"]`)) {
                expect(element.attributes('data-color-theme')).toBe('teal');
            }
        }

        expect(document.documentElement.dataset.colorTheme).toBe('neutral');
    });

    it('uses the logo preview layout for both icon fields', () => {
        const wrapper = mount(Branding, { props });
        const uploadFields = wrapper.findAllComponents({
            name: 'ImageUploadField',
        });
        const iconFields = uploadFields.filter((field) =>
            ['branding-icon', 'branding-icon-dark'].includes(
                field.props('testId'),
            ),
        );

        expect(iconFields).toHaveLength(2);

        for (const field of iconFields) {
            expect(field.props('shape')).toBe('wide');
            expect(field.props('ratio')).toBe(3);
        }
    });
});
