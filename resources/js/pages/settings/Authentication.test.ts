import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Authentication from './Authentication.vue';

const props = {
    settings: {
        requireEmailVerification: true,
        passwordPolicy: 'basic',
        sessionLifetimeMinutes: 120,
    },
    passwordPolicyOptions: [{ value: 'basic', label: 'Basic' }],
};

describe('authentication settings form', () => {
    beforeEach(resetFormState);

    it('synchronizes the switch hidden input', async () => {
        const wrapper = mount(Authentication, { props });

        expect(
            wrapper
                .get('input[name="requireEmailVerification"]')
                .attributes('value'),
        ).toBe('1');

        await wrapper.get('#requireEmailVerification').trigger('click');

        expect(
            wrapper
                .get('input[name="requireEmailVerification"]')
                .attributes('value'),
        ).toBe('0');
    });

    it('renders switch errors and processing state', () => {
        formState.errors = { requireEmailVerification: 'Invalid' };
        formState.processing = true;

        const wrapper = mount(Authentication, { props });

        expect(
            wrapper.get('#requireEmailVerification').attributes('aria-invalid'),
        ).toBe('true');
        expect(
            wrapper
                .get('[data-test="update-authentication-settings-button"]')
                .attributes(),
        ).toHaveProperty('disabled');
    });
});
