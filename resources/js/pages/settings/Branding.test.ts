import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Branding from './Branding.vue';

const option = (value: string) => [{ value, label: value }];
const props = {
    settings: {
        companyName: 'Jalara',
        footerText: null,
        authLayout: 'simple',
        appLayout: 'sidebar',
        colorTheme: 'neutral',
        fontPreset: 'instrument-sans',
    },
    authLayoutOptions: option('simple'),
    appLayoutOptions: option('sidebar'),
    colorThemeOptions: option('neutral'),
    fontPresetOptions: option('instrument-sans'),
};

describe('branding settings form', () => {
    beforeEach(resetFormState);

    it('synchronizes every radio group with a hidden input', async () => {
        const wrapper = mount(Branding, { props });
        const groups = wrapper.findAllComponents({ name: 'RadioGroup' });

        await groups[2].vm.$emit('update:modelValue', 'emerald');
        await groups[3].vm.$emit('update:modelValue', 'system-serif');

        expect(
            wrapper.get('input[name="authLayout"]').attributes('value'),
        ).toBe('simple');
        expect(wrapper.get('input[name="appLayout"]').attributes('value')).toBe(
            'sidebar',
        );
        expect(
            wrapper.get('input[name="colorTheme"]').attributes('value'),
        ).toBe('emerald');
        expect(
            wrapper.get('input[name="fontPreset"]').attributes('value'),
        ).toBe('system-serif');
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
});
