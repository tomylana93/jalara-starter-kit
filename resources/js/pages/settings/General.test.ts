import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import General from './General.vue';

const props = {
    settings: {
        applicationName: 'Jalara',
        description: null,
        defaultLocale: 'en',
        dateFormat: 'Y-m-d',
    },
    localeOptions: [{ value: 'en', label: 'English' }],
    dateFormatOptions: [{ value: 'Y-m-d', label: 'ISO' }],
};

describe('general settings form', () => {
    beforeEach(resetFormState);

    it('renders server errors and disables native constraint validation', () => {
        formState.errors = { applicationName: 'Required' };

        const wrapper = mount(General, { props });
        const input = wrapper.get('#applicationName');

        expect(input.attributes('aria-invalid')).toBe('true');
        expect(input.attributes('required')).toBeUndefined();
        expect(wrapper.text()).toContain('Required');
    });

    it('submits select values through hidden inputs and disables while busy', () => {
        formState.validating = true;

        const wrapper = mount(General, { props });

        expect(
            wrapper.get('input[name="defaultLocale"]').attributes('value'),
        ).toBe('en');
        expect(
            wrapper.get('input[name="dateFormat"]').attributes('value'),
        ).toBe('Y-m-d');
        expect(
            wrapper
                .get('[data-test="update-general-settings-button"]')
                .attributes(),
        ).toHaveProperty('disabled');
    });
});
