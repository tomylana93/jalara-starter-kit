import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ChatMessageList from '@/components/chat/ChatMessageList.vue';
import type { ChatMessage } from '@/types';

const message = (id: string, body: string, sender = 'peer'): ChatMessage => ({
    id,
    conversation_id: 'conversation-1',
    sender_id: sender,
    body,
    image: null,
    reactions: [],
    created_at: '2026-07-31T00:00:00+00:00',
});

/* The live edge Inertia sends first, already reversed for display. */
const livePage = [message('m-31', 'Message 31'), message('m-32', 'Message 32')];

/* The older page reverse infinite scroll asks for at the top. */
const olderPage = [message('m-1', 'Message 1'), message('m-2', 'Message 2')];

const bodies = (wrapper: ReturnType<typeof mount>): string[] =>
    wrapper
        .findAll('[data-message-id]')
        .map((node) =>
            node.find('[data-test="chat-message-body"]').text().trim(),
        );

describe('ChatMessageList', () => {
    it('renders each message as a registry bubble', () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: livePage,
                currentUserId: 'me',
                scrollProp: 'messages',
            },
        });

        expect(wrapper.find('[data-infinite-scroll="messages"]').exists()).toBe(
            true,
        );
        expect(bodies(wrapper)).toEqual(['Message 31', 'Message 32']);
    });

    it('prepends an older page without replacing the messages already shown', async () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: livePage,
                currentUserId: 'me',
                scrollProp: 'messages',
            },
        });

        expect(bodies(wrapper)).toEqual(['Message 31', 'Message 32']);

        /* What Inertia hands back after merging the prepended page. */
        await wrapper.setProps({ messages: [...olderPage, ...livePage] });

        expect(bodies(wrapper)).toEqual([
            'Message 1',
            'Message 2',
            'Message 31',
            'Message 32',
        ]);

        /* The originally visible rows are the same elements, not replacements. */
        expect(wrapper.find('[data-test="chat-message-m-31"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="chat-message-m-32"]').exists()).toBe(
            true,
        );
    });

    it('offers an explicit older control when Inertia does not own the paging', () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: livePage,
                currentUserId: 'me',
                hasOlder: true,
            },
        });

        expect(wrapper.find('[data-infinite-scroll]').exists()).toBe(false);
        expect(wrapper.find('[data-test="chat-load-older"]').exists()).toBe(
            true,
        );
    });

    it('reports the newest message from the other side as seen', () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: livePage,
                currentUserId: 'me',
                scrollProp: 'messages',
            },
        });

        expect(wrapper.emitted('seen')?.at(-1)).toEqual(['m-32']);
    });

    it('does not report its own message as seen', () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: [message('m-40', 'Mine', 'me')],
                currentUserId: 'me',
                scrollProp: 'messages',
            },
        });

        expect(wrapper.emitted('seen')).toBeUndefined();
    });

    it('shows the empty state when the conversation has no messages', () => {
        const wrapper = mount(ChatMessageList, {
            props: {
                messages: [],
                currentUserId: 'me',
                scrollProp: 'messages',
            },
        });

        expect(wrapper.find('[data-test="chat-messages-empty"]').exists()).toBe(
            true,
        );
    });
});
