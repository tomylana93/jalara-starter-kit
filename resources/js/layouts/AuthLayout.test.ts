import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import AuthLayout from './AuthLayout.vue';

const AuthPreset = {
    name: 'AuthPreset',
    props: ['title', 'description'],
    template: '<section><h1>{{ title }}</h1><slot /></section>',
};

const mountAuthLayout = () =>
    mount(AuthLayout, {
        props: { title: 'Log in', description: 'Welcome back' },
        slots: { default: '<p data-test="auth-content">form</p>' },
        global: {
            stubs: {
                AuthSimpleLayout: AuthPreset,
                AuthCardLayout: AuthPreset,
                AuthSplitLayout: AuthPreset,
            },
        },
    });

it('renders a single appearance toggle on auth pages', () => {
    const wrapper = mountAuthLayout();

    expect(wrapper.findAll('[data-test="appearance-toggle"]')).toHaveLength(1);
});

it('forwards the title and content slot to the branding preset', () => {
    const wrapper = mountAuthLayout();

    expect(wrapper.get('[data-test="auth-content"]').text()).toBe('form');
    expect(wrapper.text()).toContain('Log in');
});
