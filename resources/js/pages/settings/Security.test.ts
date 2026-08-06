import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Security from './Security.vue';

const props = {
    settings: {
        maxFailedLoginAttempts: 5,
        suspensionDurationMinutes: 60,
        maintenanceEnabled: false,
    },
};

describe('security settings form', () => {
    beforeEach(resetFormState);

    it('synchronizes the maintenance switch hidden input', async () => {
        const wrapper = mount(Security, { props });

        expect(
            wrapper.get('input[name="maintenanceEnabled"]').attributes('value'),
        ).toBe('0');

        await wrapper.get('#maintenanceEnabled').trigger('click');

        expect(
            wrapper.get('input[name="maintenanceEnabled"]').attributes('value'),
        ).toBe('1');
    });

    it('submits the configured thresholds', () => {
        const wrapper = mount(Security, { props });

        expect(wrapper.get('#maxFailedLoginAttempts').attributes('name')).toBe(
            'maxFailedLoginAttempts',
        );
        expect(
            wrapper.get('#suspensionDurationMinutes').attributes('name'),
        ).toBe('suspensionDurationMinutes');
    });

    it('renders switch errors and processing state', () => {
        formState.errors = { maintenanceEnabled: 'Invalid' };
        formState.processing = true;

        const wrapper = mount(Security, { props });

        expect(
            wrapper.get('#maintenanceEnabled').attributes('aria-invalid'),
        ).toBe('true');
        expect(
            wrapper
                .get('[data-test="update-security-settings-button"]')
                .attributes(),
        ).toHaveProperty('disabled');
    });
});
