import { mount } from '@vue/test-utils';
import { beforeEach, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import AppSidebar from './AppSidebar.vue';

beforeEach(() => {
    inertiaPageProps.can.manageSettings = true;
});

it('includes settings navigation when the user has permission', () => {
    const wrapper = mount(AppSidebar);
    const items = wrapper.getComponent({ name: 'NavMain' }).props('items');

    expect(items.map((item: { title: string }) => item.title)).toContain(
        'navigation.main.settings',
    );
});

it('hides settings navigation when the user lacks permission', () => {
    inertiaPageProps.can.manageSettings = false;

    const wrapper = mount(AppSidebar);
    const items = wrapper.getComponent({ name: 'NavMain' }).props('items');

    expect(items.map((item: { title: string }) => item.title)).not.toContain(
        'navigation.main.settings',
    );
});
