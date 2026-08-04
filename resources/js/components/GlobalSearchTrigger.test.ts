import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';
import GlobalSearchTrigger from './GlobalSearchTrigger.vue';

it('advertises the palette shortcut for the running platform', () => {
    const wrapper = mount(GlobalSearchTrigger);

    expect(wrapper.text()).toContain('navigation.menu.search_label');
    expect(
        wrapper.findAll('[data-slot="kbd-group"] kbd').map((key) => key.text()),
    ).toEqual(['Ctrl', 'K']);
});

it('opens the palette through the same event the icon trigger dispatches', async () => {
    const onOpen = vi.fn();
    window.addEventListener('open-global-search', onOpen);
    const wrapper = mount(GlobalSearchTrigger);

    await wrapper
        .get('[data-test="global-search-trigger-desktop"]')
        .trigger('click');

    expect(onOpen).toHaveBeenCalledOnce();
    window.removeEventListener('open-global-search', onOpen);
});
