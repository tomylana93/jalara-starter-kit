import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Create from './Create.vue';

const props = {
    roleOptions: [
        { value: 'admin', label: 'Admin' },
        { value: 'user', label: 'User' },
    ],
};

describe('create user form', () => {
    beforeEach(resetFormState);

    it('collects only a name, an email, and a role', () => {
        const wrapper = mount(Create, { props });

        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        expect(wrapper.find('input[name="email"]').exists()).toBe(true);
        expect(wrapper.find('input[name="role"]').exists()).toBe(true);
    });

    it('never renders a status control', () => {
        const wrapper = mount(Create, { props });

        expect(wrapper.find('[name="status"]').exists()).toBe(false);
        expect(wrapper.find('#status').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('master_data.user.label.status');
    });

    it('offers every assignable role and submits it through a hidden input', () => {
        const wrapper = mount(Create, { props });

        expect(wrapper.find('[data-test="role-option-admin"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="role-option-user"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.find('[data-test="role-option-super-admin"]').exists(),
        ).toBe(false);
        /* Nothing is preselected, so the server decides a missing role. */
        expect(wrapper.get('input[name="role"]').attributes('value')).toBe('');
    });

    it('renders server errors and disables native constraint validation', () => {
        formState.errors = { email: 'Already taken' };

        const wrapper = mount(Create, { props });
        const email = wrapper.get('input[name="email"]');

        expect(email.attributes('aria-invalid')).toBe('true');
        expect(email.attributes('required')).toBeUndefined();
        expect(wrapper.text()).toContain('Already taken');
    });

    it('disables the submit button while the form is busy', () => {
        formState.processing = true;

        const wrapper = mount(Create, { props });

        expect(
            wrapper.get('[data-test="save-user-button"]').attributes(),
        ).toHaveProperty('disabled');
    });
});
