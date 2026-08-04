import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import Breadcrumbs from './Breadcrumbs.vue';

const longTrail = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master data', href: '/master-data' },
    { title: 'Users', href: '/master-data/users' },
    { title: 'Tomy Maulana', href: '/master-data/users/1' },
];

it('keeps the trail on a single line and truncates the long levels', () => {
    const wrapper = mount(Breadcrumbs, { props: { breadcrumbs: longTrail } });

    expect(wrapper.get('[data-slot="breadcrumb-list"]').classes()).toContain(
        'flex-nowrap',
    );
    expect(wrapper.get('[data-slot="breadcrumb-page"]').classes()).toContain(
        'truncate',
    );
});

it('collapses the middle levels behind an ellipsis menu on narrow viewports', () => {
    const wrapper = mount(Breadcrumbs, { props: { breadcrumbs: longTrail } });

    const trigger = wrapper.get('[data-test="breadcrumb-expand"]');

    expect(trigger.attributes('aria-label')).toBe(
        'navigation.breadcrumb.expand',
    );
    expect(
        wrapper.get('[data-test="breadcrumb-expand"]').element.closest('li')
            ?.className,
    ).toContain('md:hidden');

    const inlineMiddleLevels = wrapper
        .findAll('[data-slot="breadcrumb-item"]')
        .filter((item) => item.classes().includes('md:inline-flex'));

    expect(inlineMiddleLevels).toHaveLength(2);
    expect(
        inlineMiddleLevels.every((item) => item.classes().includes('hidden')),
    ).toBe(true);
});

it('renders a short trail without the ellipsis menu', () => {
    const wrapper = mount(Breadcrumbs, {
        props: { breadcrumbs: longTrail.slice(0, 2) },
    });

    expect(wrapper.find('[data-test="breadcrumb-expand"]').exists()).toBe(
        false,
    );
    expect(wrapper.text()).toContain('Dashboard');
    expect(wrapper.get('[data-slot="breadcrumb-page"]').text()).toBe(
        'Master data',
    );
});
