import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import UserProvisioning from './UserProvisioning.vue';

describe('default password settings', () => {
    beforeEach(resetFormState);

    it('keeps password validation on the backend', () => {
        formState.errors = { defaultPassword: 'Required' };

        const wrapper = mount(UserProvisioning, {
            props: { hasDefaultPassword: false, passwordRules: '' },
        });
        const password = wrapper.get('#defaultPassword');

        expect(password.attributes('required')).toBeUndefined();
        expect(password.attributes('aria-invalid')).toBe('true');
        expect(wrapper.text()).toContain('Required');
    });

    it('shows removal confirmation only when a password exists', () => {
        const withoutPassword = mount(UserProvisioning, {
            props: { hasDefaultPassword: false, passwordRules: '' },
        });
        const withPassword = mount(UserProvisioning, {
            props: { hasDefaultPassword: true, passwordRules: '' },
        });

        expect(
            withoutPassword
                .find('[data-test="remove-default-password-button"]')
                .exists(),
        ).toBe(false);
        expect(
            withPassword
                .get('[data-test="confirm-remove-default-password-button"]')
                .text(),
        ).toContain('setting.user_provisioning.button.confirm_remove');
    });
});
