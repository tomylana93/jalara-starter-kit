import { mount } from '@vue/test-utils';
import { beforeEach, expect, it } from 'vitest';
import { formState, resetFormState } from '@/test/setup';
import Mail from './Mail.vue';

beforeEach(resetFormState);

it('keeps the email keyboard hint without native constraints', () => {
    const wrapper = mount(Mail, {
        props: {
            settings: {
                fromName: 'Jalara',
                fromAddress: 'hello@example.test',
            },
        },
    });
    const email = wrapper.get('#fromAddress');

    expect(email.attributes('inputmode')).toBe('email');
    expect(email.attributes('required')).toBeUndefined();
});

it('renders mail errors with aria-invalid', () => {
    formState.errors = { fromAddress: 'Invalid email' };

    const wrapper = mount(Mail, {
        props: {
            settings: { fromName: 'Jalara', fromAddress: '' },
        },
    });

    expect(wrapper.get('#fromAddress').attributes('aria-invalid')).toBe('true');
    expect(wrapper.text()).toContain('Invalid email');
});
