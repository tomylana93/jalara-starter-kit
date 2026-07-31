import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ChatSettingsPage from '@/pages/settings/Chat.vue';

const mountPage = (chatEnabled: boolean) =>
    mount(ChatSettingsPage, {
        props: { settings: { chatEnabled } },
    });

describe('settings/Chat', () => {
    it('submits the stored value through the hidden field', () => {
        const wrapper = mountPage(true);

        expect(
            wrapper.get('input[name="chatEnabled"]').attributes('value'),
        ).toBe('1');
    });

    it('reflects a switched-off surface', () => {
        const wrapper = mountPage(false);

        expect(
            wrapper.get('input[name="chatEnabled"]').attributes('value'),
        ).toBe('0');
    });

    it('carries the toggle into the submitted payload when flipped', async () => {
        const wrapper = mountPage(true);

        await wrapper.get('[data-test="chat-enabled-switch"]').trigger('click');

        expect(
            wrapper.get('input[name="chatEnabled"]').attributes('value'),
        ).toBe('0');
    });
});
