import { mount } from '@vue/test-utils';
import type * as VueUse from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import ChatWidget from '@/components/chat/ChatWidget.vue';
import type * as ChatClient from '@/lib/chatClient';
import { inertiaPageProps, inertiaPageUrl } from '@/test/setup';

const { desktop } = vi.hoisted(() => ({ desktop: { value: true } }));

vi.mock('@vueuse/core', async (importOriginal) => ({
    ...(await importOriginal<typeof VueUse>()),
    useMediaQuery: () => ref(desktop.value),
}));

/* jsdom has no server to talk to; the widget's data calls are not under test. */
vi.mock('@/lib/chatClient', async (importOriginal) => ({
    ...(await importOriginal<typeof ChatClient>()),
    chatRequest: vi.fn(async () => ({
        data: [],
        meta: { page: 1, perPage: 20, total: 0, lastPage: 1 },
    })),
}));

describe('ChatWidget', () => {
    beforeEach(() => {
        desktop.value = true;
        inertiaPageProps.chat.enabled = true;
        inertiaPageProps.chat.imageUploadsEnabled = true;
        inertiaPageProps.chat.unreadCount = 0;
        inertiaPageProps.auth.user = { id: 'user-1', name: 'User One' };
        inertiaPageUrl.value = '/dashboard';
        window.sessionStorage.clear();
    });

    it('renders the launcher on a desktop viewport', () => {
        const wrapper = mount(ChatWidget);

        expect(wrapper.find('[data-test="chat-widget"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="chat-widget-toggle"]').exists()).toBe(
            true,
        );
    });

    it('is not rendered below the desktop breakpoint', () => {
        desktop.value = false;

        const wrapper = mount(ChatWidget);

        expect(wrapper.find('[data-test="chat-widget"]').exists()).toBe(false);
    });

    it('is not rendered while chat is switched off', () => {
        inertiaPageProps.chat.enabled = false;

        const wrapper = mount(ChatWidget);

        expect(wrapper.find('[data-test="chat-widget"]').exists()).toBe(false);
    });

    it('stays out of the way on the dedicated chat page', () => {
        inertiaPageUrl.value = '/chat?conversation=abc';

        const wrapper = mount(ChatWidget);

        expect(wrapper.find('[data-test="chat-widget"]').exists()).toBe(false);
    });

    it('shows the aggregate unread total on the launcher', () => {
        inertiaPageProps.chat.unreadCount = 12;

        const wrapper = mount(ChatWidget);

        expect(wrapper.find('[data-test="chat-widget-badge"]').text()).toBe(
            '9+',
        );
    });

    it('restores an open window from the browser session', async () => {
        window.sessionStorage.setItem(
            'chat-widget:user-1',
            JSON.stringify({
                open: true,
                minimized: true,
                conversationId: null,
            }),
        );

        const wrapper = mount(ChatWidget);
        await wrapper.vm.$nextTick();

        const panel = wrapper.find('[data-test="chat-widget-panel"]');

        expect(panel.exists()).toBe(true);
        expect(panel.attributes('data-minimized')).toBe('true');
    });

    it('records that the window was opened so a navigation keeps it', async () => {
        const wrapper = mount(ChatWidget);

        await wrapper.find('[data-test="chat-widget-toggle"]').trigger('click');
        await wrapper.vm.$nextTick();

        expect(
            JSON.parse(
                window.sessionStorage.getItem('chat-widget:user-1') ?? '{}',
            ),
        ).toMatchObject({ open: true, minimized: false });
    });
});
