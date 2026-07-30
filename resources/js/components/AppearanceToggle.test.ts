import { mount } from '@vue/test-utils';
import { beforeEach, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';
import AppearanceToggle from './AppearanceToggle.vue';

const appearance = ref<Appearance>('system');
const systemPrefersDark = ref(false);
const updateAppearance = vi.fn((value: Appearance) => {
    appearance.value = value;
});

vi.mock('@/composables/useAppearance', () => ({
    useAppearance: () => ({
        appearance,
        resolvedAppearance: computed<ResolvedAppearance>(() => {
            if (appearance.value === 'system') {
                return systemPrefersDark.value ? 'dark' : 'light';
            }

            return appearance.value;
        }),
        updateAppearance,
    }),
}));

beforeEach(() => {
    appearance.value = 'system';
    systemPrefersDark.value = false;
    updateAppearance.mockClear();
});

it('renders a trigger with an accessible name', () => {
    const wrapper = mount(AppearanceToggle);

    expect(
        wrapper.get('[data-test="appearance-toggle"]').attributes('aria-label'),
    ).toBe('account.appearance.button.toggle');
});

it('represents the stored mode through the radio group', () => {
    appearance.value = 'dark';

    const wrapper = mount(AppearanceToggle);

    expect(wrapper.get('[role="radiogroup"]').attributes('data-value')).toBe(
        'dark',
    );
});

it.each<Appearance>(['light', 'dark', 'system'])(
    'delegates the %s selection to useAppearance exactly once',
    async (value) => {
        const wrapper = mount(AppearanceToggle);

        await wrapper
            .get(`[data-test="appearance-option-${value}"]`)
            .trigger('click');

        expect(updateAppearance).toHaveBeenCalledTimes(1);
        expect(updateAppearance).toHaveBeenCalledWith(value);
    },
);

it('shows every appearance option', () => {
    const wrapper = mount(AppearanceToggle);

    expect(
        wrapper.findAll('[role="menuitemradio"]').map((item) => item.text()),
    ).toEqual([
        'account.appearance.label.light',
        'account.appearance.label.dark',
        'account.appearance.label.system',
    ]);
});

it('reflects the resolved appearance on the trigger', async () => {
    const wrapper = mount(AppearanceToggle);
    const trigger = () => wrapper.get('[data-test="appearance-toggle"]');

    expect(trigger().attributes('data-appearance')).toBe('light');

    systemPrefersDark.value = true;
    await wrapper.vm.$nextTick();

    expect(trigger().attributes('data-appearance')).toBe('dark');
});

it('changes the appearance without navigating', async () => {
    const wrapper = mount(AppearanceToggle);

    await wrapper.get('[data-test="appearance-option-dark"]').trigger('click');

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.html()).not.toContain('href');
});
