import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PasswordInput from '@/components/PasswordInput.vue';

describe('PasswordInput', () => {
    it('starts masked and reveals on request', async () => {
        const wrapper = mount(PasswordInput, { props: { id: 'password' } });
        const input = () => wrapper.get('#password');
        const toggle = wrapper.get('button');

        expect(input().attributes('type')).toBe('password');
        expect(toggle.attributes('aria-label')).toBe(
            'common.password.button.show',
        );

        await toggle.trigger('click');

        expect(input().attributes('type')).toBe('text');
        expect(toggle.attributes('aria-label')).toBe(
            'common.password.button.hide',
        );

        await toggle.trigger('click');

        expect(input().attributes('type')).toBe('password');
    });

    it('keeps the toggle out of the tab order and out of form submission', () => {
        const wrapper = mount(PasswordInput);
        const toggle = wrapper.get('button');

        expect(toggle.attributes('tabindex')).toBe('-1');
        expect(toggle.attributes('type')).toBe('button');
    });

    it('forwards attributes to the input rather than the wrapper', () => {
        const wrapper = mount(PasswordInput, {
            props: { id: 'current_password' },
            attrs: {
                name: 'current_password',
                placeholder: 'Current password',
                'aria-invalid': 'true',
            },
        });
        const input = wrapper.get('#current_password');

        expect(input.attributes('name')).toBe('current_password');
        expect(input.attributes('placeholder')).toBe('Current password');
        expect(input.attributes('aria-invalid')).toBe('true');
    });

    /*
     * `DisableAccount` focuses this component from a form error handler, so the
     * exposed method is a contract across components, not an internal detail.
     */
    it('focuses the field through the exposed method', () => {
        const wrapper = mount(PasswordInput, {
            props: { id: 'password' },
            attachTo: document.body,
        });

        (wrapper.vm as unknown as { focus: () => void }).focus();

        expect(document.activeElement).toBe(wrapper.get('#password').element);

        wrapper.unmount();
    });
});
